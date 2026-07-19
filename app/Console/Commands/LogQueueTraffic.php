<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use App\Models\Router;
use App\Models\QueueTrafficLog;
use App\Services\MikrotikQueueService;

#[Signature('queue:log-traffic')]
#[Description('Poll active Mikrotik routers for aggregate queue bandwidth utilization and log it.')]
class LogQueueTraffic extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting queue traffic logging...');

        // 1. Get active routers
        $routers = Router::where('is_active', true)->get();

        if ($routers->isEmpty()) {
            $this->warn('No active routers found.');
            return Command::SUCCESS;
        }

        $queueService = app(MikrotikQueueService::class);

        foreach ($routers as $router) {
            $this->info("Fetching queues for Router: {$router->name} (Tenant ID: {$router->tenant_id})...");
            
            // 2. Fetch simple queues
            $queues = $queueService->getSimpleQueues($router);

            if (empty($queues)) {
                $this->error("Failed to fetch queues or no queues found for Router ID: {$router->id}.");
                continue;
            }

            // 3. Aggregate bandwidth rates
            $totalDownload = 0.0;
            $totalUpload = 0.0;

            foreach ($queues as $q) {
                $totalDownload += $q['current_tx'] ?? 0.0; // In MikrotikQueueService, current_tx is Download (Mbps)
                $totalUpload += $q['current_rx'] ?? 0.0;   // In MikrotikQueueService, current_rx is Upload (Mbps)
            }

            // 4. Save to database log
            QueueTrafficLog::create([
                'tenant_id' => $router->tenant_id,
                'router_id' => $router->id,
                'total_download_mbps' => round($totalDownload, 2),
                'total_upload_mbps' => round($totalUpload, 2),
            ]);

            $this->info("Successfully logged: DL={$totalDownload} Mbps, UL={$totalUpload} Mbps.");
        }

        // 5. Auto-cleanup logs older than 24 hours to prevent SQLite database bloat
        $deletedCount = QueueTrafficLog::withoutGlobalScopes()
            ->where('created_at', '<', now()->subHours(24))
            ->delete();

        if ($deletedCount > 0) {
            $this->info("Cleaned up {$deletedCount} old queue traffic log entries older than 24 hours.");
        }

        $this->info('Queue traffic logging completed.');
        return Command::SUCCESS;
    }
}
