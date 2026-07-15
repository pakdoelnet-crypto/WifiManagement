<?php

namespace App\Http\Controllers;

use App\Models\Odp;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OdpController extends Controller
{
    public function index()
    {
        $odps = Odp::withCount('customers')->latest()->get()->map(function($odp) {
            $odp->used_ports = $odp->customers_count;
            $odp->remaining_ports = max(0, $odp->capacity - $odp->customers_count);
            return $odp;
        });

        $customers = Customer::select('id', 'name', 'odp_id')->get();

        return Inertia::render('Odp/Index', [
            'odps' => $odps,
            'customers' => $customers,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'capacity' => 'required|integer|in:8,16,24',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'description' => 'nullable|string',
        ]);

        Odp::create($validated);

        return redirect()->back()->with('success', 'ODP berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $odp = Odp::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string',
            'capacity' => 'required|integer|in:8,16,24',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'description' => 'nullable|string',
        ]);

        $odp->update($validated);

        return redirect()->back()->with('success', 'ODP berhasil diupdate.');
    }

    public function assignCustomer(Request $request, $id)
    {
        $odp = Odp::findOrFail($id);
        
        $validated = $request->validate([
            'customer_ids' => 'required|array',
            'customer_ids.*' => 'exists:customers,id',
        ]);

        Customer::where('odp_id', $odp->id)->update(['odp_id' => null]);

        Customer::whereIn('id', $validated['customer_ids'])->update(['odp_id' => $odp->id]);

        return redirect()->back()->with('success', 'Pelanggan berhasil dipetakan ke ODP.');
    }

    public function destroy($id)
    {
        $odp = Odp::findOrFail($id);
        $odp->delete();

        return redirect()->back()->with('success', 'ODP berhasil dihapus.');
    }
}
