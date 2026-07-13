<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\Auditable;

class Router extends Model
{
    use Auditable;

    protected $fillable = [
        'name',
        'host',
        'port',
        'username',
        'password',
        'connection_type',
        'status',
        'last_checked_at',
        'is_active',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'encrypted',
        'is_active' => 'boolean',
        'last_checked_at' => 'datetime',
    ];
}
