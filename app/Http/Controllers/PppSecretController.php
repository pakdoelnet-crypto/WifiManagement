<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Services\MikrotikConnectionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PppSecretController extends Controller
{
    protected MikrotikConnectionService $connectionService;

    public function __construct(MikrotikConnectionService $connectionService)
    {
        $this->connectionService = $connectionService;
    }

    public function index(Request $request)
    {
        // Allowed roles: Super Admin, Owner, Admin, Teknisi
        if (!auth()->user()->hasAnyRole(['Super Admin', 'Owner', 'Admin', 'Teknisi'])) {
            abort(403, 'Unauthorized access to PPPoE Secrets.');
        }

        $routers = Router::where('is_active', true)->get();
        $selectedRouterId = $request->input('router_id');

        if (!$selectedRouterId && $routers->count() > 0) {
            // Default to the first online router, or just the first router
            $onlineRouter = $routers->firstWhere('status', 'online');
            $selectedRouterId = $onlineRouter ? $onlineRouter->id : $routers->first()->id;
        }

        $secrets = [];
        $profiles = [];
        $router = null;

        if ($selectedRouterId) {
            $router = Router::find($selectedRouterId);
            if ($router && $router->status === 'online') {
                // Fetch Secrets from Mikrotik
                $secretsResult = $this->connectionService->getPppSecrets($router);
                if ($secretsResult['success']) {
                    $secrets = $secretsResult['secrets'];
                }

                // Fetch Profiles from Mikrotik
                $profilesResult = $this->connectionService->getProfiles($router);
                if ($profilesResult['success']) {
                    $profiles = $profilesResult['profiles'];
                }
            }
        }

        return Inertia::render('Jaringan/PppSecrets', [
            'routers' => $routers,
            'selectedRouterId' => (int) $selectedRouterId,
            'secrets' => $secrets,
            'profiles' => $profiles,
            'routerStatus' => $router ? $router->status : 'offline',
            'canManage' => auth()->user()->hasAnyRole(['Super Admin', 'Owner', 'Admin', 'Teknisi']),
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['Super Admin', 'Owner', 'Admin', 'Teknisi'])) {
            abort(403, 'Unauthorized to create PPPoE Secret.');
        }

        $validated = $request->validate([
            'router_id' => ['required', 'exists:routers,id'],
            'name' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:50'],
            'profile' => ['required', 'string', 'max:50'],
            'comment' => ['nullable', 'string', 'max:100'],
        ]);

        $router = Router::findOrFail($validated['router_id']);
        
        if ($router->status !== 'online') {
            return redirect()->back()->with('error', 'Router sedang offline. Tidak dapat menambahkan secret.');
        }

        $secretId = $this->connectionService->addPppSecret($router, [
            'name' => $validated['name'],
            'password' => $validated['password'],
            'profile' => $validated['profile'],
        ]);

        if ($secretId) {
            // If comment is provided and RouterOS version supports comment/set, we can set comment by setting it
            // (or if we want to log it in AuditLogs)
            \App\Models\AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'ADD_PPPOE_SECRET',
                'model_type' => Router::class,
                'model_id' => (string) $router->id,
                'new_values' => [
                    'name' => $validated['name'],
                    'profile' => $validated['profile'],
                    'comment' => $validated['comment'] ?? '',
                ],
            ]);

            return redirect()->back()->with('success', "Secret PPPoE '{$validated['name']}' berhasil ditambahkan ke MikroTik.");
        }

        return redirect()->back()->with('error', 'Gagal menambahkan secret ke MikroTik. Periksa log atau koneksi router.');
    }

    public function toggle(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['Super Admin', 'Owner', 'Admin', 'Teknisi'])) {
            abort(403, 'Unauthorized to modify PPPoE Secret.');
        }

        $validated = $request->validate([
            'router_id' => ['required', 'exists:routers,id'],
            'secret_id' => ['required', 'string'],
            'name' => ['required', 'string'],
            'disabled' => ['required', 'boolean'],
        ]);

        $router = Router::findOrFail($validated['router_id']);

        if ($router->status !== 'online') {
            return redirect()->back()->with('error', 'Router sedang offline.');
        }

        // disabled = true means we disable it, so enable is false (disabled: no = active, yes = inactive)
        // togglePppSecret parameter $enable: true means disabled=no, false means disabled=yes
        $success = $this->connectionService->togglePppSecret($router, $validated['secret_id'], !$validated['disabled']);

        if ($success) {
            $statusText = $validated['disabled'] ? 'dinonaktifkan' : 'diaktifkan';
            
            \App\Models\AuditLog::create([
                'user_id' => auth()->id(),
                'action' => $validated['disabled'] ? 'DISABLE_PPPOE_SECRET' : 'ENABLE_PPPOE_SECRET',
                'model_type' => Router::class,
                'model_id' => (string) $router->id,
                'old_values' => ['name' => $validated['name']],
            ]);

            return redirect()->back()->with('success', "Secret PPPoE '{$validated['name']}' berhasil {$statusText}.");
        }

        return redirect()->back()->with('error', 'Gagal mengubah status secret di MikroTik.');
    }

    public function destroy(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['Super Admin', 'Owner', 'Admin', 'Teknisi'])) {
            abort(403, 'Unauthorized to delete PPPoE Secret.');
        }

        $validated = $request->validate([
            'router_id' => ['required', 'exists:routers,id'],
            'secret_id' => ['required', 'string'],
            'name' => ['required', 'string'],
        ]);

        $router = Router::findOrFail($validated['router_id']);

        if ($router->status !== 'online') {
            return redirect()->back()->with('error', 'Router sedang offline.');
        }

        $success = $this->connectionService->deletePppSecret($router, $validated['secret_id']);

        if ($success) {
            \App\Models\AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'DELETE_PPPOE_SECRET',
                'model_type' => Router::class,
                'model_id' => (string) $router->id,
                'old_values' => ['name' => $validated['name']],
            ]);

            return redirect()->back()->with('success', "Secret PPPoE '{$validated['name']}' berhasil dihapus dari MikroTik.");
        }

        return redirect()->back()->with('error', 'Gagal menghapus secret dari MikroTik.');
    }
}
