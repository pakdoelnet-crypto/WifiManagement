<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class WhatsappLog extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'phone_number',
        'message',
        'status',
        'type',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
