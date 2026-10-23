<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SapBusinessPartner extends Model
{
    protected $fillable = [
        'card_code',
        'card_name',
        'card_type',
        'group_code',
        'phone',
        'email',
        'address',
        'raw_data',
        'sap_synced_at',
    ];

    protected $casts = [
        'raw_data'      => 'array',
        'sap_synced_at' => 'datetime',
    ];
}
