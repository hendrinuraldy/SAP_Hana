<?php

namespace App\Console\Commands;

use App\Models\SapItem;
use App\Models\SapSyncLog;
use App\Services\SapB1Service;
use Illuminate\Console\Command;

class SyncSapItems extends Command
{
    protected $signature = 'sap:sync-items';
    protected $description = 'Sinkronisasi master Items dari SAP B1 Service Layer ke MySQL';

    public function handle(SapB1Service $sap)
    {
        $startedAt = now();
        $this->info('Mulai sync Items dari SAP...');

        try {
            $items = $sap->fetchAllForSync(
                '/Items',
                'ItemCode,ItemName,ItemsGroupCode,QuantityOnStock,AvgStdPrice,Valid'
            );

            $count = 0;
            foreach ($items as $item) {
                SapItem::updateOrCreate(
                    ['item_code' => $item['ItemCode']],
                    [
                        'item_name'       => $item['ItemName'] ?? null,
                        'item_group_code' => $item['ItemsGroupCode'] ?? null,
                        'in_stock'        => $item['QuantityOnStock'] ?? 0,
                        'avg_price'       => $item['AvgStdPrice'] ?? 0,
                        'valid_status'    => $item['Valid'] ?? null,
                        'raw_data'        => $item,
                        'sap_synced_at'   => now(),
                    ]
                );
                $count++;
            }

            SapSyncLog::create([
                'entity'         => 'Items',
                'status'         => 'success',
                'records_synced' => $count,
                'started_at'     => $startedAt,
                'finished_at'    => now(),
            ]);

            $this->info("Selesai. {$count} item berhasil di-sync.");
        } catch (\Throwable $e) {
            SapSyncLog::create([
                'entity'         => 'Items',
                'status'         => 'failed',
                'records_synced' => 0,
                'message'        => $e->getMessage(),
                'started_at'     => $startedAt,
                'finished_at'    => now(),
            ]);

            $this->error('Sync gagal: ' . $e->getMessage());
        }
    }
}
