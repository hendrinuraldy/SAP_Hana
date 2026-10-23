<?php

namespace App\Http\Controllers;

use App\Services\SapB1Service;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class SalesOrderController extends Controller
{
    protected const PER_PAGE = 50;

    // Berapa lama hasil fetch dari SAP disimpan di cache sebelum di-fetch ulang.
    // Tanpa ini, SETIAP ganti halaman (?page=2, dst) akan fetch ulang seluruh
    // data dari SAP dari awal, padahal cuma mau nampilin 50 baris.
    protected const CACHE_TTL_SECONDS = 600; // 10 menit -- fetch ini berat, jangan terlalu sering diulang

    public function index(Request $request, SapB1Service $sap)
    {
        $rows = $this->paginate([], $request); // default kosong tapi tetap punya method paginator (total, hasPages, dll)
        $columns = [];
        $error = null;
        $raw = [];
        $cachedAt = null;
        $cacheKey = 'sales_orders:open';

        if ($request->boolean('refresh')) {
            Cache::forget($cacheKey);
        }

        try {
            $cached = Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($sap) {
                // Sama seperti PurchaseOrderController: $select notasi slash buat
                // mempersempit field di dalam DocumentLines tanpa memotongnya jadi
                // kosong, plus filter tanggal 6 bulan terakhir buat kurangi jumlah
                // dokumen yang perlu di-fetch dari SAP.
                $sixMonthsAgo = now()->subMonths(6)->format('Y-m-d');

                // CATATAN: SAP versi ini TIDAK mendukung notasi slash untuk
                // mempersempit field di dalam nested collection (percobaan
                // "DocumentLines/ItemCode" balikin error "Invalid navigation
                // property: DocumentLines" / code 201). DocumentLines ditulis
                // polos supaya ikut penuh -- field header tetap dipersempit.
                $raw = $sap->getSalesOrders(
                    filter: "DocumentStatus eq 'bost_Open' and DocDate ge '{$sixMonthsAgo}'",
                    select: 'DocEntry,DocNum,DocDate,DocDueDate,CardCode,CardName,DocTotal,'
                          . 'DocumentStatus,Comments,DocumentLines'
                );

                return [
                    'rows'         => $this->flatten($raw),
                    'debug_sample' => $raw[0] ?? null,
                    'fetched_at'   => now(),
                ];
            });

            $allRows = $cached['rows'];
            $columns = $allRows ? array_keys($allRows[0]) : [];
            $rows = $this->paginate($allRows, $request);
            $raw = $cached['debug_sample'] ? [$cached['debug_sample']] : [];
            $cachedAt = $cached['fetched_at'];
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return view('reports.sales-order', [
            'rows'     => $rows,
            'columns'  => $columns,
            'error'    => $error,
            'debugRaw' => $raw,
            'cachedAt' => $cachedAt,
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
     * Pecah tiap Sales Order + line item-nya jadi 1 baris per item.
     * Entity Orders sudah menyertakan ItemCode dan ItemDescription langsung
     * di DocumentLines, jadi tidak perlu enrich tambahan seperti PickLists.
     */
    protected function flatten(array $orders): array
    {
        $flat = [];

        foreach ($orders as $order) {
            $lines = $order['DocumentLines'] ?? [];

            if (empty($lines)) {
                $flat[] = [
                    'SO No'      => $order['DocNum'] ?? null,
                    'SO Date'    => $order['DocDate'] ?? null,
                    'Due Date'   => $order['DocDueDate'] ?? null,
                    'Customer'   => $order['CardName'] ?? null,
                    'Status'     => $order['DocumentStatus'] ?? null,
                    'Remarks'    => $order['Comments'] ?? null,
                    'Item Code'  => null,
                    'Item Name'  => null,
                    'Warehouse'  => null,
                    'Qty'        => null,
                    'Price'      => null,
                    'Line Total' => null,
                    'Doc Total'  => $order['DocTotal'] ?? null,
                ];
                continue;
            }

            foreach ($lines as $line) {
                $flat[] = [
                    'SO No'      => $order['DocNum'] ?? null,
                    'SO Date'    => $order['DocDate'] ?? null,
                    'Due Date'   => $order['DocDueDate'] ?? null,
                    'Customer'   => $order['CardName'] ?? null,
                    'Status'     => $order['DocumentStatus'] ?? null,
                    'Remarks'    => $order['Comments'] ?? null,
                    'Item Code'  => $line['ItemCode'] ?? null,
                    'Item Name'  => $line['ItemDescription'] ?? null,
                    'Warehouse'  => $line['WarehouseCode'] ?? null,
                    'Qty'        => $line['Quantity'] ?? null,
                    'Price'      => $line['Price'] ?? null,
                    'Line Total' => $line['LineTotal'] ?? null,
                    'Doc Total'  => $order['DocTotal'] ?? null,
                ];
            }
        }

        return $flat;
    }
}