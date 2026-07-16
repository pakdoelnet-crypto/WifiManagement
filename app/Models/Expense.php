<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Expense extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'category',
        'amount',
        'description',
        'expense_date',
    ];

    protected $casts = [
        'amount' => 'float',
        'expense_date' => 'date',
    ];
}
