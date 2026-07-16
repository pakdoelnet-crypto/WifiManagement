<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class InventoryItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
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
