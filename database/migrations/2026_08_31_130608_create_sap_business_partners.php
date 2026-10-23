<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sap_business_partners', function (Blueprint $table) {
            $table->id();
            $table->string('card_code')->unique();   // CardCode di SAP
            $table->string('card_name')->nullable();
            $table->string('card_type')->nullable();  // C = Customer, S = Supplier, L = Lead
            $table->string('group_code')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamp('sap_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sap_business_partners');
    }
};
