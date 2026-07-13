<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceController extends Controller
{

    public function index(Request $request)
    {
        $query = Invoice::with(['customer.package', 'payment']);

        // Search: PPPoE username or Customer name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($sub) use ($search) {
                      $sub->where('name', 'like', "%{$search}%")
                          ->orWhere('pppoe_username', 'like', "%{$search}%");
                  });
            });
        }

        // Filter: Customer Name / ID
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter: Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter: Periode (format: YYYYMM)
        if ($request->filled('periode')) {
            $query->where('periode', $request->periode);
        }

        $invoices = $query->latest('due_date')->paginate(15)->withQueryString();

        // Get list of customers for filter dropdown
        $customers = Customer::select('id', 'name', 'pppoe_username')->get();

        // Get unique periods for filter dropdown
        $periodes = Invoice::select('periode')
            ->whereNotNull('periode')
            ->distinct()
            ->orderBy('periode', 'desc')
            ->pluck('periode');

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'customers' => $customers,
            'periodes' => $periodes,
            'filters' => $request->only(['search', 'customer_id', 'status', 'periode']),
        ]);
    }
}
