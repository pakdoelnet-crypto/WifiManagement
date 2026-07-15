<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function index()
    {
        $items = InventoryItem::with('logs.user')->latest()->get();
        return Inertia::render('Inventory/Index', [
            'items' => $items,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'sku' => 'required|string|unique:inventory_items,sku',
            'category' => 'required|string|in:Router,Switch,ONU,OLT,Kabel,Adaptor,SFP,Splitter,Connector',
            'unit' => 'required|string',
            'stock_qty' => 'required|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $item = InventoryItem::create($validated);

        if ($validated['stock_qty'] > 0) {
            InventoryLog::create([
                'inventory_item_id' => $item->id,
                'type' => 'in',
                'quantity' => $validated['stock_qty'],
                'notes' => 'Stok awal barang',
                'logged_by_user_id' => auth()->id(),
            ]);
        }

        return redirect()->back()->with('success', 'Barang inventori berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $item = InventoryItem::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string',
            'sku' => 'required|string|unique:inventory_items,sku,' . $item->id,
            'category' => 'required|string|in:Router,Switch,ONU,OLT,Kabel,Adaptor,SFP,Splitter,Connector',
            'unit' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $item->update($validated);

        return redirect()->back()->with('success', 'Barang inventori berhasil diupdate.');
    }

    public function adjustStock(Request $request, $id)
    {
        $item = InventoryItem::findOrFail($id);

        $validated = $request->validate([
            'type' => 'required|string|in:in,out',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        if ($validated['type'] === 'out' && $item->stock_qty < $validated['quantity']) {
            return redirect()->back()->withErrors(['quantity' => 'Stok tidak mencukupi untuk dikeluarkan.']);
        }

        if ($validated['type'] === 'in') {
            $item->stock_qty += $validated['quantity'];
        } else {
            $item->stock_qty -= $validated['quantity'];
        }
        $item->save();

        InventoryLog::create([
            'inventory_item_id' => $item->id,
            'type' => $validated['type'],
            'quantity' => $validated['quantity'],
            'notes' => $validated['notes'],
            'logged_by_user_id' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Stok berhasil disesuaikan.');
    }

    public function destroy($id)
    {
        $item = InventoryItem::findOrFail($id);
        $item->delete();

        return redirect()->back()->with('success', 'Barang inventori berhasil dihapus.');
    }
}
