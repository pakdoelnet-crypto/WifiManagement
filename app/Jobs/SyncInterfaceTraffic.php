<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use App\Models\Router;
use App\Services\MikrotikConnectionService;
use App\Events\InterfaceTrafficUpdated;

class SyncInterfaceTraffic implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cacheKey = 'monitored_router_interface';
        $runningFlag = 'sync_interface_traffic_running';

        // Keep running for 45 seconds per dispatch (well within queue timeouts)
        $startTime = time();
        while (time() - $startTime < 45) {
            // Check if there is still a user watching (cache key hasn't expired)
            if (!Cache::has($cacheKey)) {
                break;
            }

            // Extend the running flag lease
            Cache::put($runningFlag, true, 10);

            $monitor = Cache::get($cacheKey);
            $routerId = $monitor['router_id'] ?? null;
            $interfaceName = $monitor['interface'] ?? null;

            if ($routerId && $interfaceName) {
                $router = Router::find($routerId);
                if ($router && $router->status === 'online' && $router->is_active) {
                    $connectionService = app(MikrotikConnectionService::class);
                    $result = $connectionService->getInterfaceTraffic($router, $interfaceName);
                    
                    if ($result['success']) {
                        broadcast(new InterfaceTrafficUpdated(
                            $interfaceName,
                            $result['data']['rx'],
                            $result['data']['tx']
                        ));
                    }
                }
            }

            sleep(3); // Poll every 3 seconds
        }

        // Clean up flag when done
        Cache::forget($runningFlag);
    }
}
