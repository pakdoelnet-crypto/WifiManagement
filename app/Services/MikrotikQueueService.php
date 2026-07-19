<?php

namespace App\Services;

use RouterOS\Client;
use RouterOS\Config;
use RouterOS\Query;
use App\Models\Router;

class MikrotikQueueService
{
    /**
     * Get a configured and connected RouterOS client instance.
     */
    public function getClient(?Router $router = null): Client
    {
        $host = null;
        $port = null;
        $username = null;
        $password = null;

        if ($router) {
            $host = $router->host;
            $port = $router->port;
            $username = $router->username;
            $password = $router->password;
        } else {
            // 1. Try reading from env first
            $host = env('MIKROTIK_HOST');
            $port = env('MIKROTIK_PORT');
            $username = env('MIKROTIK_USERNAME');
            $password = env('MIKROTIK_PASSWORD');

            // 2. If env not fully configured, fall back to the first active Router in DB
            if (empty($host) || empty($username)) {
                $dbRouter = Router::where('is_active', true)->first();
                if ($dbRouter) {
                    $host = $dbRouter->host;
                    $port = $dbRouter->port;
                    $username = $dbRouter->username;
                    $password = $dbRouter->password;
                }
            }
        }

        $config = new Config([
            'host' => $host ?? '127.0.0.1',
            'port' => (int) ($port ?? 8728),
            'user' => $username ?? 'admin',
            'pass' => $password ?? '',
            'ssl' => false,
            'timeout' => 5,
            'throw_timeout_exception' => false,
        ]);

        return new Client($config);
    }

    /**
     * Fetch simple queues from Mikrotik Router.
     */
    public function getSimpleQueues(?Router $router = null): array
    {
        try {
            $client = $this->getClient($router);
            $response = $client->query('/queue/simple/print')->read();

            if (!is_array($response)) {
                return [];
            }

            $queues = [];
            foreach ($response as $item) {
                // Parse max-limit (format: upload/download in bits/sec)
                $maxLimit = $item['max-limit'] ?? '0/0';
                $limits = explode('/', $maxLimit);
                $maxUploadBytes = (int) ($limits[0] ?? 0);
                $maxDownloadBytes = (int) ($limits[1] ?? 0);
                
                $maxUploadMbps = round($maxUploadBytes / 1024 / 1024, 1);
                $maxDownloadMbps = round($maxDownloadBytes / 1024 / 1024, 1);

                // Parse current rate (format: upload/download in bits/sec)
                $currentRate = $item['rate'] ?? '0/0';
                $rates = explode('/', $currentRate);
                $currUploadBytes = (int) ($rates[0] ?? 0);
                $currDownloadBytes = (int) ($rates[1] ?? 0);

                $currUploadMbps = round($currUploadBytes / 1024 / 1024, 2);
                $currDownloadMbps = round($currDownloadBytes / 1024 / 1024, 2);

                // Calculate usage percentage based on download (TX) limit
                $usagePercent = 0;
                if ($maxDownloadBytes > 0) {
                    $usagePercent = min(100, round(($currDownloadBytes / $maxDownloadBytes) * 100));
                }

                // Determine queue status: aktif, idle, or throttled (terpotong)
                $status = 'idle';
                if ($currDownloadBytes > 10000 || $currUploadBytes > 10000) {
                    $status = $usagePercent > 90 ? 'terpotong' : 'aktif';
                }

                $queues[] = [
                    'name' => $item['name'] ?? 'Unknown',
                    'target' => $item['target'] ?? 'N/A',
                    'max_limit_tx' => $maxDownloadMbps, // Download Limit (Mbps)
                    'max_limit_rx' => $maxUploadMbps,   // Upload Limit (Mbps)
                    'current_tx' => $currDownloadMbps,   // Current Download (Mbps)
                    'current_rx' => $currUploadMbps,     // Current Upload (Mbps)
                    'burst_limit' => $item['burst-limit'] ?? 'N/A',
                    'usage' => $usagePercent,
                    'status' => $status
                ];
            }

            return $queues;

        } catch (\Exception $e) {
            \Log::error('MikrotikQueueService error: ' . $e->getMessage());
            return [];
        }
    }
}
