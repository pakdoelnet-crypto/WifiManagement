<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\Auditable;

class Package extends Model
{
    use Auditable;

    protected $fillable = [
        'name',
        'upload_limit',
        'download_limit',
        'price',
        'mikrotik_profile',
        'is_active',
    ];

    protected $casts = [
        'upload_limit' => 'integer',
        'download_limit' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
