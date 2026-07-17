<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\Auditable;
use App\Traits\BelongsToTenant;

class Customer extends Model
{
    use Auditable, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
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

    protected static function booted()
    {
        static::created(function ($customer) {
            if ($customer->package_id) {
                $periode = date('Ym');
                $year = substr($periode, 0, 4);
                $month = substr($periode, 4, 2);
                
                $invoiceNumber = 'INV-' . $periode . '-' . str_pad($customer->id, 4, '0', STR_PAD_LEFT);
                $dueDate = date('Y-m-d', strtotime("$year-$month-10"));
                
                $package = $customer->package;
                $price = $package ? $package->price : 0;

                \App\Models\Invoice::create([
                    'tenant_id' => $customer->tenant_id,
                    'customer_id' => $customer->id,
                    'invoice_number' => $invoiceNumber,
                    'amount' => $price,
                    'periode' => $periode,
                    'penalty_amount' => 0,
                    'discount_amount' => 0,
                    'total_amount' => $price,
                    'due_date' => $dueDate,
                    'status' => 'unpaid',
                ]);
            }
        });
    }

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function currentInvoice()
    {
        return $this->hasOne(Invoice::class)->where('periode', date('Ym'));
    }
}
