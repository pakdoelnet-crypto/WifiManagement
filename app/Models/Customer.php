<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\Auditable;

class Customer extends Model
{
    use Auditable;

    protected $fillable = [
        'router_id',
        'package_id',
        'name',
        'phone',
        'whatsapp',
        'email',
        'ktp_number',
        'ktp_photo_path',
        'photo_path',
        'address',
        'lat',
        'lng',
        'pppoe_username',
        'pppoe_password',
        'pppoe_secret_id',
        'status',
        'source',
        'joined_at',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'joined_at' => 'date',
    ];

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
