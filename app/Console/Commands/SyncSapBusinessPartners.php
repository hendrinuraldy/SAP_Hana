<?php

namespace App\Console\Commands;

use App\Models\SapBusinessPartner;
use App\Models\SapSyncLog;
use App\Services\SapB1Service;
use Illuminate\Console\Command;

class SyncSapBusinessPartners extends Command
{
    protected $signature = 'sap:sync-business-partners';
    protected $description = 'Sinkronisasi master Business Partners dari SAP B1 Service Layer ke MySQL';

    public function handle(SapB1Service $sap)
    {
        $startedAt = now();
        $this->info('Mulai sync Business Partners dari SAP...');

        try {
            $partners = $sap->fetchAllForSync(
                '/BusinessPartners',
                'CardCode,CardName,CardType,GroupCode,Phone1,EmailAddress,Address'
            );

            $count = 0;
            foreach ($partners as $bp) {
                SapBusinessPartner::updateOrCreate(
                    ['card_code' => $bp['CardCode']],
                    [
                        'card_name'     => $bp['CardName'] ?? null,
                        'card_type'     => $bp['CardType'] ?? null,
                        'group_code'    => $bp['GroupCode'] ?? null,
                        'phone'         => $bp['Phone1'] ?? null,
                        'email'         => $bp['EmailAddress'] ?? null,
                        'address'       => $bp['Address'] ?? null,
                        'raw_data'      => $bp,
                        'sap_synced_at' => now(),
                    ]
                );
                $count++;
            }

            SapSyncLog::create([
                'entity'         => 'BusinessPartners',
                'status'         => 'success',
                'records_synced' => $count,
                'started_at'     => $startedAt,
                'finished_at'    => now(),
            ]);

            $this->info("Selesai. {$count} business partner berhasil di-sync.");
        } catch (\Throwable $e) {
            SapSyncLog::create([
                'entity'         => 'BusinessPartners',
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
