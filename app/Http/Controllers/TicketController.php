<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::with(['customer', 'assignedUser'])->latest()->get();
        $customers = Customer::select('id', 'name', 'whatsapp')->get();
        $technicians = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['Super Admin', 'Admin', 'Teknisi']);
        })->get();

        return Inertia::render('Tickets/Index', [
            'tickets' => $tickets,
            'customers' => $customers,
            'technicians' => $technicians,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'category' => 'required|string',
            'priority' => 'required|string|in:low,medium,high',
            'assigned_user_id' => 'nullable|exists:users,id',
            'status' => 'required|string|in:open,diproses,selesai',
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

        $ticket = Ticket::create([
            'ticket_number' => $ticketNumber,
            'customer_id' => $validated['customer_id'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'assigned_user_id' => $validated['assigned_user_id'],
            'status' => $validated['status'],
            'notes' => $validated['notes'],
            'photo_path' => $photoPath,
            'reported_at' => now(),
            'resolved_at' => $validated['status'] === 'selesai' ? now() : null,
        ]);

        return redirect()->back()->with('success', 'Ticket Gangguan berhasil dibuat.');
    }

    public function update(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $validated = $request->validate([
            'category' => 'required|string',
            'priority' => 'required|string|in:low,medium,high',
            'assigned_user_id' => 'nullable|exists:users,id',
            'status' => 'required|string|in:open,diproses,selesai',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            if ($ticket->photo_path) {
                Storage::disk('public')->delete($ticket->photo_path);
            }
            $ticket->photo_path = $request->file('photo')->store('tickets', 'public');
        }

        $ticket->category = $validated['category'];
        $ticket->priority = $validated['priority'];
        $ticket->assigned_user_id = $validated['assigned_user_id'];
        
        $oldStatus = $ticket->status;
        $ticket->status = $validated['status'];
        $ticket->notes = $validated['notes'];

        if ($validated['status'] === 'selesai' && $oldStatus !== 'selesai') {
            $ticket->resolved_at = now();
        } elseif ($validated['status'] !== 'selesai') {
            $ticket->resolved_at = null;
        }

        $ticket->save();

        return redirect()->back()->with('success', 'Ticket Gangguan berhasil diupdate.');
    }

    public function destroy($id)
    {
        $ticket = Ticket::findOrFail($id);
        if ($ticket->photo_path) {
            Storage::disk('public')->delete($ticket->photo_path);
        }
        $ticket->delete();

        return redirect()->back()->with('success', 'Ticket Gangguan berhasil dihapus.');
    }
}
