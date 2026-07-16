<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\Auditable;
use App\Traits\BelongsToTenant;

class NetworkPoint extends Model
{
    use Auditable, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'type',
        'name',
        'lat',
        'lng',
        'capacity',
        'radius_meters',
        'parent_id',
        'notes',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'capacity' => 'integer',
        'radius_meters' => 'integer',
    ];

    public function parent()
    {
        return $this->belongsTo(NetworkPoint::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(NetworkPoint::class, 'parent_id');
    }
}
