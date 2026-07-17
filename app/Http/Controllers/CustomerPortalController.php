<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerPortalController extends Controller
{
    private function getCustomer()
    {
        $customerId = session('customer_id');
        if (!$customerId) {
            abort(401, 'Unauthorized');
        }
        return Customer::with(['router', 'package'])->findOrFail($customerId);
    }

    public function index()
    {
        $customer = $this->getCustomer();
        
        $invoices = Invoice::where('customer_id', $customer->id)
            ->with('payment')
            ->latest()
            ->get();

        $tickets = Ticket::where('customer_id', $customer->id)
            ->latest()
            ->get();

        return Inertia::render('CustomerPortal/Dashboard', [
            'customer' => $customer,
            'invoices' => $invoices,
            'tickets' => $tickets,
        ]);
    }

    public function reportTicket(Request $request)
    {
        $customer = $this->getCustomer();

        $validated = $request->validate([
            'category' => 'required|string',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
        ]);

        $dateStr = date('Ymd');
        $lastTicket = Ticket::whereDate('created_at', date('Y-m-d'))
            ->orderBy('id', 'desc')
            ->first();
        
        $seq = 1;
        if ($lastTicket) {
            $parts = explode('-', $lastTicket->ticket_number);
            $seq = (int)end($parts) + 1;
        }
        $ticketNumber = 'TKT-' . $dateStr . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('tickets', 'public');
        }

        Ticket::create([
            'tenant_id' => $customer->tenant_id,
            'ticket_number' => $ticketNumber,
            'customer_id' => $customer->id,
            'category' => $validated['category'],
            'priority' => 'medium',
            'assigned_user_id' => null,
            'status' => 'open',
            'notes' => $validated['notes'],
            'photo_path' => $photoPath,
            'reported_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Gangguan berhasil dilaporkan.');
    }

    public function changePassword(Request $request)
    {
        $customer = $this->getCustomer();

        $validated = $request->validate([
            'new_password' => 'required|string|min:4',
        ]);

        $customer->pppoe_password = $validated['new_password'];
        $customer->save();

        try {
            $router = $customer->router;
            if ($router) {
                $connectionService = app(\App\Services\MikrotikConnectionService::class);
                
                if (method_exists($connectionService, 'updatePppSecret')) {
                    $connectionService->updatePppSecret($router, $customer->pppoe_username, [
                        'password' => $validated['new_password']
                    ]);
                } else {
                    $client = $connectionService->connect($router);
                    if ($client) {
                        $secrets = $client->query('/ppp/secret/print', ['?name' => $customer->pppoe_username])->read();
                        if (!empty($secrets)) {
                            $client->query('/ppp/secret/set', [
                                '.id' => $secrets[0]['.id'],
                                'password' => $validated['new_password']
                            ])->read();
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Log or ignore
        }

        return redirect()->back()->with('success', 'Password PPPoE berhasil diubah.');
    }
}
