<?php

namespace App\Http\Controllers;

use App\Models\SapItem;
use App\Services\SapB1Service;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class PickingListController extends Controller
{
    protected const PER_PAGE = 50;

    // Picking List ini paling berat dibanding laporan lain -- selain fetch
    // PickLists, dia juga fetch tambahan (enrich) ke Sales Order satu-satu
    // untuk dapat Item Code/Name. Tanpa cache, SETIAP ganti halaman akan
    // mengulang semua proses itu dari nol. Jadi cache-nya dibikin lebih
    // penting di sini, bukan cuma nice-to-have.
    protected const CACHE_TTL_SECONDS = 120;

    public function index(Request $request, SapB1Service $sap)
    {
        $rows = $this->paginate([], $request); // default kosong tapi tetap punya method paginator (total, hasPages, dll)
        $columns = [];
        $error = null;
        $raw = [];
        $enrichWarning = null;
        $cachedAt = null;
        $cacheKey = 'picking_list:open';

        if ($request->boolean('refresh')) {
            Cache::forget($cacheKey);
        }

        try {
            $cached = Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($sap) {
                $raw = $sap->getPickLists("Status ne 'ps_Closed'");
                [$allRows, $enrichErrors] = $this->flatten($raw, $sap);

                return [
                    'rows'          => $allRows,
                    'enrichErrors'  => $enrichErrors,
                    'debug_sample'  => $raw[0] ?? null,
                    'fetched_at'    => now(),
                ];
            });

            $allRows = $cached['rows'];

            if (!empty($cached['enrichErrors'])) {
                $enrichWarning = $this->summarizeEnrichErrors($cached['enrichErrors'], count($allRows));
            }

            $columns = $allRows ? array_keys($allRows[0]) : [];
            $rows = $this->paginate($allRows, $request);
            $raw = $cached['debug_sample'] ? [$cached['debug_sample']] : [];
            $cachedAt = $cached['fetched_at'];
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return view('reports.picking-list', [
            'rows'          => $rows,
            'columns'       => $columns,
            'error'         => $error,
            'enrichWarning' => $enrichWarning,
            'debugRaw'      => $raw,
            'cachedAt'      => $cachedAt,
        ]);
    }

    /**
     * Bungkus array biasa (bukan hasil query Eloquent) jadi LengthAwarePaginator,
     * supaya bisa pakai {{ $rows->links() }} dan navigasi ?page=N seperti biasa.
     */
    protected function paginate(array $items, Request $request): LengthAwarePaginator
    {
        $page = (int) $request->input('page', 1);
        $page = $page < 1 ? 1 : $page;

        $slice = array_slice($items, ($page - 1) * self::PER_PAGE, self::PER_PAGE);

        return new LengthAwarePaginator(
            $slice,
            count($items),
            self::PER_PAGE,
            $page,
            [
                'path'  => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    /**
     * Ringkas daftar error enrich (per dokumen basis yang gagal di-fetch)
     * jadi satu pesan warning yang enak dibaca, dengan deteksi khusus
     * untuk kasus izin akses (403/401) supaya user tau harus ngapain.
     *
     * @param array<int, array{endpoint:string, entry:int, message:string}> $enrichErrors
     */
    protected function summarizeEnrichErrors(array $enrichErrors, int $totalRows): string
    {
        $count = count($enrichErrors);
        $sample = $enrichErrors[0];

        $looksLikePermission = collect($enrichErrors)->contains(function ($e) {
            return str_contains($e['message'], '403')
                || str_contains($e['message'], '401')
                || stripos($e['message'], 'unauthor') !== false
                || stripos($e['message'], 'not accessible') !== false
                || stripos($e['message'], 'no authorization') !== false;
        });

        $endpoints = collect($enrichErrors)->pluck('endpoint')->unique()->implode(', ');

        $msg = "Gagal mengambil detail Item Code/Name untuk {$count} dokumen basis ({$endpoints}). "
             . "Contoh error: \"{$sample['message']}\" (DocEntry {$sample['entry']}).";

        if ($looksLikePermission) {
            $msg .= ' Kemungkinan user SAP yang dipakai tidak punya izin akses ke modul terkait '
                  . '(cek Administration > System Initialization > Authorizations untuk endpoint di atas). '
                  . 'Baris yang terdampak tetap tampil, tapi kolom Item Code/Item Name-nya kosong.';
        } else {
            $msg .= ' Baris yang terdampak tetap tampil, tapi kolom Item Code/Item Name-nya kosong.';
        }

        return $msg;
    }

    /**
     * Pecah tiap Pick List + line item-nya jadi 1 baris per item.
     * Karena PickListsLines dari SAP TIDAK punya ItemCode/ItemName langsung,
     * kita harus join manual ke dokumen basis (Sales Order dkk) via
     * OrderEntry (DocEntry) + OrderRowID (LineNum), lalu enrich nama item
     * dari cache MySQL (SapItem) sebagai fallback.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array{endpoint:string, entry:int, message:string}>}
     */
    protected function flatten(array $pickLists, SapB1Service $sap): array
    {
        $flat = [];
        $enrichErrors = []; // kumpulan error saat fetch dokumen basis, buat ditampilkan sebagai warning

        // 1. Kumpulkan semua referensi dokumen basis yang unik (biar tidak N+1 request ke SAP)
        $baseDocs = []; // key: "{type}-{entry}" => ['type'=>.., 'entry'=>..]
        foreach ($pickLists as $pl) {
            foreach (($pl['PickListsLines'] ?? []) as $line) {
                $entry = $line['OrderEntry'] ?? null;
                if ($entry === null) {
                    continue;
                }
                // Fallback ke 17 (Sales Order) kalau field BaseObjectType tidak ada di response.
                $type = $line['BaseObjectType'] ?? $line['ObjectType'] ?? 17;
                $baseDocs["{$type}-{$entry}"] = ['type' => $type, 'entry' => $entry];
            }
        }

        // 2. Fetch tiap dokumen basis sekali, bikin map "{type}-{entry}-{lineNum}" -> [code, name]
        $lineItemMap = [];
        foreach ($baseDocs as $doc) {
            $endpoint = $this->resolveEndpoint((int) $doc['type']);
            if (!$endpoint) {
                continue;
            }

            try {
                $docData = $sap->getDocumentByEntry($endpoint, (int) $doc['entry'], 'DocumentLines');
            } catch (\Throwable $e) {
                // Jangan sampai jatuhin seluruh report — cukup catat, lanjut ke dokumen berikutnya.
                $enrichErrors[] = [
                    'endpoint' => $endpoint,
                    'entry'    => $doc['entry'],
                    'message'  => $e->getMessage(),
                ];
                continue;
            }

            foreach (($docData['DocumentLines'] ?? []) as $docLine) {
                $lineNum = $docLine['LineNum'] ?? null;
                if ($lineNum === null) {
                    continue;
                }
                $key = "{$doc['type']}-{$doc['entry']}-{$lineNum}";
                $lineItemMap[$key] = [
                    'code' => $docLine['ItemCode'] ?? null,
                    'name' => $docLine['ItemDescription'] ?? null,
                ];
            }
        }

        // 3. Flatten pick list, enrich pakai map di atas
        foreach ($pickLists as $pl) {
            $lines = $pl['PickListsLines'] ?? [];

            if (empty($lines)) {
                $flat[] = [
                    'Pick No'     => $pl['Absoluteentry'] ?? null,
                    'Pick Date'   => $pl['PickDate'] ?? null,
                    'Status'      => $pl['Status'] ?? null,
                    'Remarks'     => $pl['Remarks'] ?? null,
                    'Item Code'   => null,
                    'Item Name'   => null,
                    'Release Qty' => null,
                    'Picked Qty'  => null,
                ];
                continue;
            }

            foreach ($lines as $line) {
                $entry = $line['OrderEntry'] ?? null;
                $type  = $line['BaseObjectType'] ?? $line['ObjectType'] ?? 17;
                $rowId = $line['OrderRowID'] ?? null;
                $key   = "{$type}-{$entry}-{$rowId}";

                $itemCode = $lineItemMap[$key]['code'] ?? null;
                $itemName = $lineItemMap[$key]['name'] ?? null;

                // Fallback ke cache MySQL kalau nama item dari SAP kosong
                if ($itemCode && !$itemName) {
                    $itemName = optional(SapItem::where('item_code', $itemCode)->first())->item_name;
                }

                $flat[] = [
                    'Pick No'     => $pl['Absoluteentry'] ?? null,
                    'Pick Date'   => $pl['PickDate'] ?? null,
                    'Status'      => $pl['Status'] ?? null,
                    'Remarks'     => $pl['Remarks'] ?? null,
                    'Item Code'   => $itemCode,
                    'Item Name'   => $itemName,
                    'Release Qty' => $line['ReleasedQuantity'] ?? null,
                    'Picked Qty'  => $line['PickedQuantity'] ?? null,
                ];
            }
        }

        return [$flat, $enrichErrors];
    }

    /**
     * Mapping BaseObjectType (kode dokumen SAP) -> endpoint Service Layer.
     * Sesuaikan/tambah kalau ternyata Pick List kamu juga berasal dari
     * dokumen lain (mis. Reserve Invoice, Delivery, dll).
     */
    protected function resolveEndpoint(int $baseObjectType): ?string
    {
        return match ($baseObjectType) {
            17 => 'Orders',   // Sales Order
            15 => 'Invoices', // A/R Invoice
            default => 'Orders', // default aman: kebanyakan pick list dari Sales Order
        };
    }
}