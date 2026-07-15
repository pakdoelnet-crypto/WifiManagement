<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'category',
        'stock_qty',
        'unit',
        'description',
    ];

    protected $casts = [
        'stock_qty' => 'integer',
    ];

    public function logs()
    {
        return $this->hasMany(InventoryLog::class);
    }
}
