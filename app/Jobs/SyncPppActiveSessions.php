<?php

namespace App\Jobs;

use App\Models\Router;
use App\Models\Customer;
use App\Models\PppActiveSession;
use App\Models\CustomerSessionLog;
use App\Services\MikrotikConnectionService;
use App\Events\PppActiveSessionsUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncPppActiveSessions implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(MikrotikConnectionService $connectionService): void
    {
        $activeRouters = Router::where('is_active', true)->get();

        foreach ($activeRouters as $router) {
            // Get currently stored active sessions from DB
            $oldSessions = PppActiveSession::where('router_id', $router->id)
                ->get()
                ->keyBy('pppoe_username');

            // Fetch live sessions from Mikrotik Router
            $result = $connectionService->getPppActiveSessions($router);

            if (!$result['success']) {
                \Log::warning("SyncPppActiveSessions failed for Router {$router->name}: " . $result['error']);
                continue;
            }

            $sessions = $result['sessions'];
            $newSessionMap = collect($sessions)->keyBy('name');
            $usernames = array_keys($newSessionMap->toArray());

            // Map usernames to customer IDs
            $customerMap = Customer::whereIn('pppoe_username', $usernames)
                ->pluck('id', 'pppoe_username')
                ->toArray();

            // 1. Detect Logins (Present in new, not in old)
            foreach ($sessions as $session) {
                $username = $session['name'];
                if (!$oldSessions->has($username)) {
                    $customerId = $customerMap[$username] ?? null;
                    
                    CustomerSessionLog::create([
                        'customer_id' => $customerId,
                        'pppoe_username' => $username,
                        'router_id' => $router->id,
                        'ip_address' => $session['address'],
                        'event_type' => 'login',
                        'session_started_at' => now(),
                        'session_ended_at' => null,
                        'duration_seconds' => null,
                    ]);
                }
            }

            // 2. Detect Logouts (Present in old, not in new)
            foreach ($oldSessions as $username => $oldSession) {
                if (!$newSessionMap->has($username)) {
                    // Find corresponding login event log that is not ended
                    $loginLog = CustomerSessionLog::where('pppoe_username', $username)
                        ->where('router_id', $router->id)
                        ->where('event_type', 'login')
                        ->whereNull('session_ended_at')
                        ->latest('session_started_at')
                        ->first();

                    $startedAt = $loginLog ? $loginLog->session_started_at : now()->subMinutes(10);
                    $endedAt = now();
                    $duration = $endedAt->diffInSeconds($startedAt);

                    // Update login log
                    if ($loginLog) {
                        $loginLog->update([
                            'session_ended_at' => $endedAt,
                            'duration_seconds' => $duration,
                        ]);
                    }

                    // Create logout event log
                    CustomerSessionLog::create([
                        'customer_id' => $oldSession->customer_id,
                        'pppoe_username' => $username,
                        'router_id' => $router->id,
                        'ip_address' => $oldSession->ip_address,
                        'event_type' => 'logout',
                        'session_started_at' => $startedAt,
                        'session_ended_at' => $endedAt,
                        'duration_seconds' => $duration,
                    ]);
                }
            }

            // Update local Active Sessions records
            foreach ($sessions as $session) {
                PppActiveSession::updateOrCreate(
                    [
                        'router_id' => $router->id,
                        'pppoe_username' => $session['name'],
                    ],
                    [
                        'customer_id' => $customerMap[$session['name']] ?? null,
                        'ip_address' => $session['address'],
                        'uptime' => $session['uptime'],
                        'caller_id' => $session['caller_id'],
                        'interface_name' => '<pppoe-' . $session['name'] . '>',
                        'last_seen_at' => now(),
                    ]
                );
            }

            // Clean up disconnected sessions
            PppActiveSession::where('router_id', $router->id)
                ->whereNotIn('pppoe_username', $usernames)
                ->delete();
        }

        // Broadcast latest updated sessions list
        $latestSessions = PppActiveSession::with(['router', 'customer'])->get()->toArray();
        broadcast(new PppActiveSessionsUpdated($latestSessions));
    }
}
