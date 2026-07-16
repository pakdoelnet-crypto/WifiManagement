<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class PppActiveSession extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'router_id',
        'customer_id',
        'pppoe_username',
        'ip_address',
        'uptime',
        'caller_id',
        'interface_name',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
