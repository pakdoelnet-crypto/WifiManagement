<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Payment;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        // Check authorization (Super Admin, Owner, Admin, Customer Service, Kasir)
        if (!auth()->user()->hasAnyRole(['Super Admin', 'Owner', 'Admin', 'Customer Service', 'Kasir'])) {
            abort(403, 'Unauthorized access to invoices.');
        }

        // Fail-safe auto-generation: Ensure all active customers have current month's invoice
        $periode = date('Ym');
        $year = substr($periode, 0, 4);
        $month = substr($periode, 4, 2);
        
        $activeCustomers = Customer::where('status', 'active')
            ->whereNotNull('package_id')
            ->get();

        foreach ($activeCustomers as $customer) {
            $exists = Invoice::where('customer_id', $customer->id)
                ->where('periode', $periode)
                ->exists();

            if (!$exists) {
                $package = $customer->package;
                if ($package) {
                    $invoiceNumber = 'INV-' . $periode . '-' . str_pad($customer->id, 4, '0', STR_PAD_LEFT);
                    $dueDate = date('Y-m-d', strtotime("$year-$month-10"));

                    Invoice::create([
                        'customer_id' => $customer->id,
                        'invoice_number' => $invoiceNumber,
                        'amount' => $package->price,
                        'periode' => $periode,
                        'penalty_amount' => 0,
                        'discount_amount' => 0,
                        'total_amount' => $package->price,
                        'due_date' => $dueDate,
                        'status' => 'unpaid',
                    ]);
                }
            }
        }

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

        // Get unique periods for filter dropdown, defaulting to include recent months
        $dbPeriodes = Invoice::select('periode')
            ->whereNotNull('periode')
            ->distinct()
            ->orderBy('periode', 'desc')
            ->pluck('periode')
            ->toArray();

        // Generate past 6 months and next 2 months default periods
        $defaultPeriods = [];
        for ($i = -2; $i <= 6; $i++) {
            $defaultPeriods[] = date('Ym', strtotime("-$i month"));
        }
        
        $periodes = array_unique(array_merge($dbPeriodes, $defaultPeriods));
        rsort($periodes);

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'customers' => $customers,
            'periodes' => $periodes,
            'filters' => $request->only(['search', 'customer_id', 'status', 'periode']),
            'canManage' => auth()->user()->hasAnyRole(['Super Admin', 'Owner', 'Admin', 'Kasir']),
        ]);
    }

    /**
     * Generate monthly invoices for all active customers.
     */
    public function generate(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['Super Admin', 'Owner', 'Admin'])) {
            abort(403, 'Unauthorized to generate invoices.');
        }

        $request->validate([
            'periode' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ]);

        $periode = $request->periode;
        $year = substr($periode, 0, 4);
        $month = substr($periode, 4, 2);
        
        // Find all active customers who have an internet package assigned
        $activeCustomers = Customer::where('status', 'active')
            ->whereNotNull('package_id')
            ->get();

        $generatedCount = 0;

        foreach ($activeCustomers as $customer) {
            // Check if invoice already exists for this period
            $exists = Invoice::where('customer_id', $customer->id)
                ->where('periode', $periode)
                ->exists();

            if (!$exists) {
                $package = $customer->package;
                if (!$package) continue;

                // Unique invoice number (INV-YYYYMM-CUSTID)
                $invoiceNumber = 'INV-' . $periode . '-' . str_pad($customer->id, 4, '0', STR_PAD_LEFT);
                $dueDate = date('Y-m-d', strtotime("$year-$month-10"));

                Invoice::create([
                    'customer_id' => $customer->id,
                    'invoice_number' => $invoiceNumber,
                    'amount' => $package->price,
                    'periode' => $periode,
                    'penalty_amount' => 0,
                    'discount_amount' => 0,
                    'total_amount' => $package->price,
                    'due_date' => $dueDate,
                    'status' => 'unpaid',
                ]);

                $generatedCount++;
            }
        }

        return redirect()->back()->with('success', "$generatedCount invoice tagihan baru untuk periode " . $this->formatPeriodName($periode) . " berhasil dibuat.");
    }

    /**
     * Record payment transaction.
     */
    public function pay(Request $request, $id)
    {
        if (!auth()->user()->hasAnyRole(['Super Admin', 'Owner', 'Admin', 'Kasir'])) {
            abort(403, 'Unauthorized to process payment.');
        }

        $request->validate([
            'payment_method' => ['required', 'string', 'in:Tunai,Transfer Bank,QRIS,Lainnya'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $invoice = Invoice::findOrFail($id);

        if ($invoice->status === 'paid') {
            return redirect()->back()->with('error', 'Invoice ini sudah lunas.');
        }

        // Create Payment Transaction
        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => $invoice->total_amount ?? $invoice->amount,
            'payment_method' => $request->payment_method,
            'payment_date' => now(),
            'notes' => $request->notes,
        ]);

        // Update Invoice status
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // If the customer was isolated/disabled, restore their status to active!
        $customer = $invoice->customer;
        if ($customer && $customer->status !== 'active') {
            app(CustomerService::class)->toggleStatus($customer->id, 'active');
        }

        return redirect()->back()->with('success', "Pembayaran untuk invoice {$invoice->invoice_number} berhasil dicatat. Status pelanggan telah diaktifkan kembali.");
    }

    public function publicView($invoice_number)
    {
        $invoice = Invoice::with(['customer.package', 'payment'])
            ->where('invoice_number', $invoice_number)
            ->firstOrFail();

        return Inertia::render('Invoices/Public', [
            'invoice' => $invoice
        ]);
    }

    private function formatPeriodName($periodStr)
    {
        if (strlen($periodStr) !== 6) return $periodStr;
        $year = substr($periodStr, 0, 4);
        $monthIndex = (int) substr($periodStr, 4, 2) - 1;
        $monthNames = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        return $monthNames[$monthIndex] . ' ' . $year;
    }
}
