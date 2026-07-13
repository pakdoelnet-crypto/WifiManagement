<?php

namespace App\Http\Controllers;

use App\Models\PppActiveSession;
use App\Models\Router;
use App\Models\AuditLog;
use App\Services\MikrotikConnectionService;
use App\Events\PppActiveSessionsUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PppActiveSessionController extends Controller
{
    protected MikrotikConnectionService $connectionService;

    public function __construct(MikrotikConnectionService $connectionService)
    {
        $this->connectionService = $connectionService;
    }

    public function index()
    {
        // View active sessions (read-only for CS/Teknisi, full access for Super Admin/Owner/Admin)
        if (!auth()->user()->hasAnyRole(['Super Admin', 'Owner', 'Admin', 'Customer Service', 'Kasir', 'Teknisi'])) {
            abort(403, 'Unauthorized access to online customers.');
        }

        $sessions = PppActiveSession::with(['router', 'customer.package'])->get();

        return Inertia::render('Customers/Online', [
            'sessions' => $sessions,
            'canManage' => auth()->user()->hasAnyRole(['Super Admin', 'Owner', 'Admin']),
        ]);
    }

    public function disconnect(Request $request)
    {
        // Only Super Admin, Owner, Admin can disconnect sessions
        if (!auth()->user()->hasAnyRole(['Super Admin', 'Owner', 'Admin'])) {
            abort(403, 'Unauthorized to disconnect customer session.');
        }

        $request->validate([
            'id' => ['required', 'exists:ppp_active_sessions,id'],
        ]);

        $session = PppActiveSession::with('router')->findOrFail($request->id);
        $router = $session->router;

        if ($router && $router->is_active) {
            $success = $this->connectionService->disconnectPppSession($router, $session->pppoe_username);
            
            if ($success) {
                // Log activity in audit logs
                AuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'DISCONNECT_PPP_SESSION',
                    'model_type' => PppActiveSession::class,
                    'model_id' => (string) $session->id,
                    'old_values' => [
                        'pppoe_username' => $session->pppoe_username,
                        'router_name' => $router->name,
                        'ip_address' => $session->ip_address,
                        'uptime' => $session->uptime,
                    ],
                    'new_values' => null,
                ]);

                // Delete local record
                $session->delete();

                // Broadcast latest sessions list
                $latestSessions = PppActiveSession::with(['router', 'customer'])->get()->toArray();
                broadcast(new PppActiveSessionsUpdated($latestSessions));

                return redirect()->back()->with('success', "Koneksi PPPoE pelanggan {$session->pppoe_username} berhasil diputuskan.");
            }
        }

        return redirect()->back()->with('error', 'Gagal memutuskan koneksi pelanggan dari MikroTik. Pastikan router aktif.');
    }
}
