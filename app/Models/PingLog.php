<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class PingLog extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
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
