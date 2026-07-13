<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\CustomerSessionLog;
use App\Models\Router;
use App\Models\Package;
use Carbon\Carbon;

class InvoiceAndLogSeeder extends Seeder
{
    public function run(): void
    {
        // Clean existing tables to avoid duplicate key issues during re-seeding
        Invoice::query()->delete();
        Payment::query()->delete();
        CustomerSessionLog::query()->delete();

        // 1. Ensure we have at least 1 Router, 1 Package and 3 Customers
        $router = Router::firstOrCreate(
            ['host' => '192.168.88.1'],
            [
                'name' => 'Router Utama',
                'port' => 8728,
                'username' => 'admin',
                'password' => 'password',
                'connection_type' => 'api',
                'status' => 'online',
                'is_active' => true,
            ]
        );

        $package = Package::firstOrCreate(
            ['name' => 'Premium 50 Mbps'],
            [
                'upload_limit' => 51200,
                'download_limit' => 51200,
                'price' => 250000,
                'mikrotik_profile' => 'default',
                'is_active' => true,
            ]
        );

        $customer1 = Customer::firstOrCreate(
            ['email' => 'budi@gmail.com'],
            [
                'name' => 'Budi Santoso',
                'phone' => '081234567800',
                'whatsapp' => '081234567800',
                'pppoe_username' => 'budi_client',
                'pppoe_password' => 'password',
                'router_id' => $router->id,
                'package_id' => $package->id,
                'address' => 'Kepanjen, Malang',
                'source' => 'created_by_app',
                'joined_at' => Carbon::now()->subMonths(5),
            ]
        );

        $customer2 = Customer::firstOrCreate(
            ['email' => 'ani@gmail.com'],
            [
                'name' => 'Ani Lestari',
                'phone' => '081234567801',
                'whatsapp' => '081234567801',
                'pppoe_username' => 'ani_client',
                'pppoe_password' => 'password',
                'router_id' => $router->id,
                'package_id' => $package->id,
                'address' => 'Kepanjen, Malang',
                'source' => 'created_by_app',
                'joined_at' => Carbon::now()->subMonths(4),
            ]
        );

        $customer3 = Customer::firstOrCreate(
            ['email' => 'joko@gmail.com'],
            [
                'name' => 'Joko Susilo',
                'phone' => '081234567802',
                'whatsapp' => '081234567802',
                'pppoe_username' => 'joko_client',
                'pppoe_password' => 'password',
                'router_id' => $router->id,
                'package_id' => $package->id,
                'address' => 'Kepanjen, Malang',
                'source' => 'created_by_app',
                'joined_at' => Carbon::now()->subMonths(2),
            ]
        );

        // 2. Generate invoices for the last 6 months
        $customers = [$customer1, $customer2, $customer3];
        $monthsToSeed = 6;

        for ($i = 0; $i < $monthsToSeed; $i++) {
            $monthDate = Carbon::now()->subMonths($i);
            
            foreach ($customers as $c) {
                // If customer joined after this monthDate, skip
                if ($c->joined_at->gt($monthDate)) {
                    continue;
                }

                $status = 'paid';
                $paidAt = $monthDate->copy()->addDays(rand(1, 10));
                
                if ($i === 0) {
                    $status = rand(0, 2) === 0 ? 'paid' : (rand(0, 1) === 0 ? 'unpaid' : 'overdue');
                    if ($status !== 'paid') {
                        $paidAt = null;
                    }
                }

                $price = $package->price;
                $penalty = ($status === 'overdue') ? 25000 : 0;
                $discount = (rand(0, 4) === 0) ? 10000 : 0; // 20% chance of discount
                $total = $price + $penalty - $discount;

                $invoice = Invoice::create([
                    'customer_id' => $c->id,
                    'invoice_number' => 'INV-' . sprintf('%05d', rand(1, 99999)) . '-' . $monthDate->format('Ym'),
                    'amount' => $price,
                    'periode' => $monthDate->format('Ym'),
                    'penalty_amount' => $penalty,
                    'discount_amount' => $discount,
                    'total_amount' => $total,
                    'due_date' => $monthDate->copy()->startOfMonth()->addDays(15),
                    'status' => $status,
                    'paid_at' => $paidAt,
                    'created_at' => $monthDate->copy()->startOfMonth(),
                    'updated_at' => $monthDate->copy()->startOfMonth(),
                ]);

                // Create payment if paid
                if ($status === 'paid') {
                    Payment::create([
                        'invoice_id' => $invoice->id,
                        'amount' => $total,
                        'payment_method' => rand(0, 1) === 0 ? 'Transfer Bank' : 'Cash/Tunai',
                        'payment_date' => $paidAt,
                        'notes' => 'Pembayaran lunas via seeder',
                        'created_at' => $paidAt,
                        'updated_at' => $paidAt,
                    ]);
                }
            }
        }

        // 3. Generate some session logs
        foreach ($customers as $c) {
            // Still active login
            CustomerSessionLog::create([
                'customer_id' => $c->id,
                'pppoe_username' => $c->pppoe_username,
                'router_id' => $router->id,
                'ip_address' => '10.10.10.' . (10 + $c->id),
                'event_type' => 'login',
                'session_started_at' => Carbon::now()->subHours(rand(1, 12)),
                'session_ended_at' => null,
                'duration_seconds' => null,
            ]);

            // Ended logs
            for ($k = 1; $k <= 3; $k++) {
                $started = Carbon::now()->subDays($k)->subHours(rand(1, 12));
                $duration = rand(3600, 28800); // 1 to 8 hours
                $ended = $started->copy()->addSeconds($duration);

                // Create login log
                CustomerSessionLog::create([
                    'customer_id' => $c->id,
                    'pppoe_username' => $c->pppoe_username,
                    'router_id' => $router->id,
                    'ip_address' => '10.10.10.' . (10 + $c->id),
                    'event_type' => 'login',
                    'session_started_at' => $started,
                    'session_ended_at' => $ended,
                    'duration_seconds' => $duration,
                ]);

                // Create logout log
                CustomerSessionLog::create([
                    'customer_id' => $c->id,
                    'pppoe_username' => $c->pppoe_username,
                    'router_id' => $router->id,
                    'ip_address' => '10.10.10.' . (10 + $c->id),
                    'event_type' => 'logout',
                    'session_started_at' => $started,
                    'session_ended_at' => $ended,
                    'duration_seconds' => $duration,
                ]);
            }
        }
    }
}
