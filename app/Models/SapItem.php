<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SapItem extends Model
{
    protected $fillable = [
        'item_code',
        'item_name',
        'item_group_code',
        'uom_group',
        'in_stock',
        'avg_price',
        'valid_status',
        'raw_data',
        'sap_synced_at',
    ];

    protected $casts = [
        'raw_data'      => 'array',
        'sap_synced_at' => 'datetime',
        'in_stock'      => 'decimal:4',
        'avg_price'     => 'decimal:4',
    ];
}
