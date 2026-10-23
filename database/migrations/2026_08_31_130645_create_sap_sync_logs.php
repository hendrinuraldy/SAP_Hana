<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sap_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('entity');            // 'Items' / 'BusinessPartners'
            $table->string('status');            // 'success' / 'failed'
            $table->integer('records_synced')->default(0);
            $table->text('message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sap_sync_logs');
    }
};
