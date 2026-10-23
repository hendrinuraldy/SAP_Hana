<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sap_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->unique();      // ItemCode di SAP
            $table->string('item_name')->nullable();     // ItemName
            $table->string('item_group_code')->nullable();
            $table->string('uom_group')->nullable();     // UoMGroupEntry / satuan
            $table->decimal('in_stock', 18, 4)->default(0);
            $table->decimal('avg_price', 18, 4)->default(0);
            $table->string('valid_status')->nullable();  // 'tYES' / 'tNO'
            $table->json('raw_data')->nullable();         // simpan full JSON asli dari SAP kalau perlu detail lain
            $table->timestamp('sap_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sap_items');
    }
};
