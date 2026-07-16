<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\Auditable;
use App\Traits\BelongsToTenant;

class FiberRoute extends Model
{
    use Auditable, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'from_point_id',
        'to_point_id',
        'path_geojson',
        'length_meters',
    ];

    protected $casts = [
        'path_geojson' => 'array',
        'length_meters' => 'integer',
    ];

    public function fromPoint()
    {
        return $this->belongsTo(NetworkPoint::class, 'from_point_id');
    }

    public function toPoint()
    {
        return $this->belongsTo(NetworkPoint::class, 'to_point_id');
    }
}
