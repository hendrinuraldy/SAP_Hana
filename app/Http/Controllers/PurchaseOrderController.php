<?php

namespace App\Http\Controllers;

use App\Services\SapB1Service;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class PurchaseOrderController extends Controller
{
    protected const PER_PAGE = 50;

    // Berapa lama hasil fetch dari SAP disimpan di cache sebelum di-fetch ulang.
    // Ini yang bikin ganti halaman (?page=2, dst) terasa instan -- tanpa cache,
    // SETIAP ganti halaman akan fetch ulang seluruh data (bisa ribuan baris)
    // dari SAP dari awal, padahal cuma mau nampilin 50 baris.
    protected const CACHE_TTL_SECONDS = 600; // 10 menit -- fetch ini berat, jangan terlalu sering diulang

    public function index(Request $request, SapB1Service $sap)
    {
        $rows = $this->paginate([], $request); // default kosong tapi tetap punya method paginator (total, hasPages, dll)
        $columns = [];
        $error = null;
        $raw = [];
        $cacheKey = 'purchase_orders:open';

        // Tombol/link "Refresh Data" bisa kirim ?refresh=1 buat paksa ambil
        // data terbaru dari SAP, lewat dari cache yang masih berlaku.
        if ($request->boolean('refresh')) {
            Cache::forget($cacheKey);
        }

        try {
            $cached = Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($sap) {
                // PENTING soal performa: sebelumnya kita fetch TANPA $select sama
                // sekali, artinya SAP narik SEMUA field per dokumen (PurchaseOrders
                // punya ratusan field, termasuk yang jarang dipakai). Ini bikin
                // fetch 1000+ baris bisa 1+ menit.
                //
                // Fix: pakai $select dengan notasi slash ("DocumentLines/ItemCode")
                // -- ini cara resmi SAP Service Layer buat mempersempit field DI
                // DALAM nested collection tanpa memotongnya jadi kosong (beda
                // dengan nulis "DocumentLines" polos tanpa nama field, yang
                // sebelumnya bikin ItemCode/Comments ke-skip).
                //
                // Ditambah filter tanggal 6 bulan terakhir -- PO yang masih
                // 'Open' dari bertahun-tahun lalu biasanya bukan yang relevan
                // buat laporan operasional harian, dan ini paling besar
                // ngurangin jumlah dokumen yang perlu di-fetch.
                $sixMonthsAgo = now()->subMonths(6)->format('Y-m-d');

                // CATATAN: SAP versi ini TIDAK mendukung notasi slash untuk
                // mempersempit field di dalam nested collection (percobaan
                // "DocumentLines/ItemCode" balikin error "Invalid navigation
                // property: DocumentLines" / code 201). Jadi DocumentLines
                // harus ditulis polos supaya ikut penuh apa adanya -- field
                // header yang tetap dipersempit di sini.
                $raw = $sap->getPurchaseOrders(
                    filter: "DocumentStatus eq 'bost_Open' and DocDate ge '{$sixMonthsAgo}'",
                    select: 'DocEntry,DocNum,DocDate,DocDueDate,CardCode,CardName,DocTotal,'
                          . 'DocumentStatus,Comments,DocumentLines'
                );

                return [
                    'rows'         => $this->flatten($raw),
                    'debug_sample' => $raw[0] ?? null, // cuma simpan 1 contoh, bukan semua raw (biar cache tidak bengkak)
                    'fetched_at'   => now(),
                ];
            });

            $allRows = $cached['rows'];
            $columns = $allRows ? array_keys($allRows[0]) : [];
            $rows = $this->paginate($allRows, $request);
            $raw = $cached['debug_sample'] ? [$cached['debug_sample']] : [];
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return view('reports.purchase-order', [
            'rows'     => $rows,
            'columns'  => $columns,
            'error'    => $error,
            'debugRaw' => $raw,
            'cachedAt' => $cached['fetched_at'] ?? null,
        ]);
    }

    /**
     * Bungkus array biasa jadi LengthAwarePaginator, sama seperti di
     * SalesOrderController/PickingListController.
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
     * Pecah tiap Purchase Order + line item-nya jadi 1 baris per item.
     * Sama seperti Orders (Sales), entity PurchaseOrders sudah menyertakan
     * ItemCode dan ItemDescription langsung di DocumentLines.
     * Catatan: field "CardName" di sini artinya Vendor, bukan Customer
     * (nama field-nya sama di SAP walau konteksnya beda Sales/Purchasing).
     */
    protected function flatten(array $purchaseOrders): array
    {
        $flat = [];

        foreach ($purchaseOrders as $po) {
            $lines = $po['DocumentLines'] ?? [];

            if (empty($lines)) {
                $flat[] = [
                    'PO No'      => $po['DocNum'] ?? null,
                    'PO Date'    => $po['DocDate'] ?? null,
                    'Due Date'   => $po['DocDueDate'] ?? null,
                    'Vendor'     => $po['CardName'] ?? null,
                    'Status'     => $po['DocumentStatus'] ?? null,
                    'Remarks'    => $po['Comments'] ?? null,
                    'Item Code'  => null,
                    'Item Name'  => null,
                    'Warehouse'  => null,
                    'Qty'        => null,
                    'Price'      => null,
                    'Line Total' => null,
                    'Doc Total'  => $po['DocTotal'] ?? null,
                ];
                continue;
            }

            foreach ($lines as $line) {
                $flat[] = [
                    'PO No'      => $po['DocNum'] ?? null,
                    'PO Date'    => $po['DocDate'] ?? null,
                    'Due Date'   => $po['DocDueDate'] ?? null,
                    'Vendor'     => $po['CardName'] ?? null,
                    'Status'     => $po['DocumentStatus'] ?? null,
                    'Remarks'    => $po['Comments'] ?? null,
                    'Item Code'  => $line['ItemCode'] ?? null,
                    'Item Name'  => $line['ItemDescription'] ?? null,
                    'Warehouse'  => $line['WarehouseCode'] ?? null,
                    'Qty'        => $line['Quantity'] ?? null,
                    'Price'      => $line['Price'] ?? null,
                    'Line Total' => $line['LineTotal'] ?? null,
                    'Doc Total'  => $po['DocTotal'] ?? null,
                ];
            }
        }

        return $flat;
    }
}