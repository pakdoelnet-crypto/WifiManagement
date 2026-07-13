<?php

namespace App\Http\Controllers;

use App\Models\CustomerSessionLog;
use App\Models\Router;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerSessionLogController extends Controller
{
    public function index(Request $request)
    {
        // Check authorization (Super Admin, Owner, Admin, Customer Service, Kasir, Teknisi can see connection logs)
        if (!auth()->user()->hasAnyRole(['Super Admin', 'Owner', 'Admin', 'Customer Service', 'Kasir', 'Teknisi'])) {
            abort(403, 'Unauthorized access to customer connection history.');
        }

        $query = CustomerSessionLog::with(['customer', 'router'])
            ->where('event_type', 'login'); // Show login records because they contain duration/logout time

        // Filter: Customer Name
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        } elseif ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('pppoe_username', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter: Router
        if ($request->filled('router_id')) {
            $query->where('router_id', $request->router_id);
        }

        // Filter: Date Range
        if ($request->filled('start_date')) {
            $query->whereDate('session_started_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('session_started_at', '<=', $request->end_date);
        }

        $logs = $query->latest('session_started_at')->paginate(25)->withQueryString();

        $routers = Router::where('is_active', true)->get();
        $customers = Customer::select('id', 'name', 'pppoe_username')->get();

        return Inertia::render('Customers/ConnectionHistory', [
            'logs' => $logs,
            'routers' => $routers,
            'customers' => $customers,
            'filters' => $request->only(['customer_id', 'router_id', 'start_date', 'end_date', 'search']),
        ]);
    }
}
