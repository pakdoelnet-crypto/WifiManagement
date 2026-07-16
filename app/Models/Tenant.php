<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_usaha',
        'subdomain',
        'status',
        'paket_langganan',
        'logo_url',
    ];

    /**
     * Get the users associated with the tenant.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
