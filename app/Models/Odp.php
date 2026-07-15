<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Odp extends Model
{
    protected $fillable = [
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
