<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\Auditable;
use App\Traits\BelongsToTenant;

class Package extends Model
{
    use Auditable, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
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
