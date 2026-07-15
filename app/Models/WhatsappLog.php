<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
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
