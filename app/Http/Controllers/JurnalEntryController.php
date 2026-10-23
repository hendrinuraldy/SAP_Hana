<?php

namespace App\Http\Controllers;

use App\Services\SapB1Service;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class JournalEntryController extends Controller
{
    protected const PER_PAGE = 50;

    public const PERIOD_OPTIONS = [
        '1'   => 'Bulan Ini',
        '3'   => '3 Bulan Terakhir',
        '6'   => '6 Bulan Terakhir',
        '12'  => '1 Tahun Terakhir',
        'all' => 'Semua (tanpa batas tanggal)',
    ];

    // Default jangka waktu lebih pendek dibanding Sales/Purchase Order,
    // karena jumlah Journal Entry biasanya JAUH lebih banyak (dibuat harian,
    // termasuk otomatis dari transaksi lain) -- kalau default-nya 6 bulan
    // atau lebih, fetch pertama bisa berat sekali.
    protected const DEFAULT_PERIOD = '1';

    protected const CACHE_TTL_SECONDS = 600; // 10 menit

    public function index(Request $request, SapB1Service $sap)
    {
        $rows = $this->paginate([], $request);
        $columns = [];
        $error = null;
        $raw = [];
        $cachedAt = null;

        $period = $request->input('period', self::DEFAULT_PERIOD);
        if (!array_key_exists($period, self::PERIOD_OPTIONS)) {
            $period = self::DEFAULT_PERIOD;
        }

        $cacheKey = "journal_entries:period_{$period}";

        if ($request->boolean('refresh')) {
            Cache::forget($cacheKey);
        }

        try {
            $cached = Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($sap, $period) {
                // CATATAN: nama field di bawah ini (ReferenceDate, JdtNum, dll)
                // adalah nama field standar SAP B1 Service Layer untuk entity
                // JournalEntries. Kalau ternyata versi SAP HANA kamu beda dikit
                // (sama seperti kasus PickListsLines dulu), buka panel Debug di
                // halaman ini buat cek nama field asli dan kasih tau saya biar
                // disesuaikan.
                $filter = null;

                if ($period !== 'all') {
                    $sinceDate = now()->subMonths((int) $period)->format('Y-m-d');
                    $filter = "ReferenceDate ge '{$sinceDate}'";
                }

                // DocumentLines/JournalEntryLines ditulis polos (bukan notasi
                // slash) -- sama seperti Sales/Purchase Order, SAP versi ini
                // tidak mendukung notasi slash untuk mempersempit field nested.
                $raw = $sap->getJournalEntries(
                    filter: $filter,
                    select: 'JdtNum,TransId,ReferenceDate,DueDate,Memo,Reference,Reference2,JournalEntryLines'
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

        return view('reports.journal-entry', [
            'rows'          => $rows,
            'columns'       => $columns,
            'error'         => $error,
            'debugRaw'      => $raw,
            'cachedAt'      => $cachedAt,
            'period'        => $period,
            'periodOptions' => self::PERIOD_OPTIONS,
        ]);
    }

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
     * Pecah tiap Journal Entry + baris jurnalnya jadi 1 baris per akun.
     * Beda dari laporan lain: tidak ada Item Code/Qty, yang ada Account
     * Code + Debit/Credit per baris.
     */
    protected function flatten(array $journalEntries): array
    {
        $flat = [];

        foreach ($journalEntries as $je) {
            $lines = $je['JournalEntryLines'] ?? [];

            if (empty($lines)) {
                $flat[] = [
                    'JE No'        => $je['JdtNum'] ?? $je['TransId'] ?? null,
                    'Posting Date' => $je['ReferenceDate'] ?? null,
                    'Due Date'     => $je['DueDate'] ?? null,
                    'Reference'    => $je['Reference'] ?? null,
                    'Memo'         => $je['Memo'] ?? null,
                    'Account Code' => null,
                    'Account Name' => null,
                    'Debit'        => null,
                    'Credit'       => null,
                    'Line Memo'    => null,
                ];
                continue;
            }

            foreach ($lines as $line) {
                $flat[] = [
                    'JE No'        => $je['JdtNum'] ?? $je['TransId'] ?? null,
                    'Posting Date' => $je['ReferenceDate'] ?? null,
                    'Due Date'     => $je['DueDate'] ?? null,
                    'Reference'    => $je['Reference'] ?? null,
                    'Memo'         => $je['Memo'] ?? null,
                    'Account Code' => $line['AccountCode'] ?? null,
                    'Account Name' => $line['ShortName'] ?? null,
                    'Debit'        => $line['Debit'] ?? null,
                    'Credit'       => $line['Credit'] ?? null,
                    'Line Memo'    => $line['LineMemo'] ?? null,
                ];
            }
        }

        return $flat;
    }
}