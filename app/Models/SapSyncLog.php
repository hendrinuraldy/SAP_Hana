<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SapSyncLog extends Model
{
    protected $fillable = [
        'entity',
        'status',
        'records_synced',
        'message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];
}
