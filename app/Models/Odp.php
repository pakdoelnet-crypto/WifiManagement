<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Odp extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'capacity',
        'lat',
        'lng',
        'description',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'lat' => 'float',
        'lng' => 'float',
    ];

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }
}
