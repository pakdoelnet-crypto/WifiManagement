<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PingLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'host',
        'latency_ms',
        'status',
        'checked_at',
    ];

    protected $casts = [
        'latency_ms' => 'integer',
        'checked_at' => 'datetime',
    ];
}
