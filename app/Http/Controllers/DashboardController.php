<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Router;
use App\Models\PppActiveSession;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Total Customers count
        $totalCustomers = Customer::count();

        // 2. Online Customers (Active PPPoE Sessions)
        $onlineCustomers = PppActiveSession::count();

        // 3. Router & CPU Load details
        $routers = Router::where('is_active', true)->get()->map(function ($router) {
            // Mock CPU Load if Online, otherwise 0
            $cpuLoad = 0;
            if ($router->status === 'online') {
                // Generate consistent but slightly dynamic CPU load based on router ID
                $cpuLoad = (($router->id * 7) % 30) + 10; 
            }
            return [
                'id' => $router->id,
                'name' => $router->name,
                'status' => $router->status,
                'cpu_load' => $cpuLoad,
            ];
        });

        // 4. Invoices Due This Month
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $invoicesDueThisMonth = Invoice::whereIn('status', ['unpaid', 'overdue'])
            ->whereMonth('due_date', $currentMonth)
            ->whereYear('due_date', $currentYear)
            ->sum('amount');

        // 5. Revenue last 6 months (Paid Invoices)
        $revenue6Months = [
            'labels' => [],
            'data' => [],
        ];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $sum = Invoice::where('status', 'paid')
                ->whereMonth('paid_at', $month->month)
                ->whereYear('paid_at', $month->year)
                ->sum('amount');
            
            $revenue6Months['labels'][] = $month->translatedFormat('M Y');
            $revenue6Months['data'][] = (float) $sum;
        }

        // 6. New Customers last 6 months
        $newCustomers6Months = [
            'labels' => [],
            'data' => [],
        ];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $count = Customer::whereMonth('joined_at', $month->month)
                ->whereYear('joined_at', $month->year)
                ->count();
            
            $newCustomers6Months['labels'][] = $month->translatedFormat('M Y');
            $newCustomers6Months['data'][] = $count;
        }

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalCustomers' => $totalCustomers,
                'onlineCustomers' => $onlineCustomers,
                'routers' => $routers,
                'invoicesDueThisMonth' => (float) $invoicesDueThisMonth,
            ],
            'charts' => [
                'revenue' => $revenue6Months,
                'newCustomers' => $newCustomers6Months,
            ]
        ]);
    }
}
