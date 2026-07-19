<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Models\PingLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NocDashboardController extends Controller
{
    public function index()
    {
        $routers = Router::where('is_active', true)->get();
        $totalRouter = $routers->count();
        $onlineRouter = 0;
        $offlineRouter = 0;
        $highCpu = 0;
        $highRam = 0;

        $connectionService = app(\App\Services\MikrotikConnectionService::class);

        $routerDetails = [];
        foreach ($routers as $router) {
            $status = 'offline';
            $cpu = 0;
            $ramPercent = 0;
            $uptime = '-';

            try {
                $res = $connectionService->testConnection($router);
                if ($res['success']) {
                    $status = 'online';
                    $onlineRouter++;
                    
                    // Parse CPU Load
                    $cpu = (int) str_replace('%', '', $res['data']['cpuLoad'] ?? '0');
                    if ($cpu > 80) {
                        $highCpu++;
                    }

                    $uptime = $res['data']['uptime'] ?? '-';
                    
                    // Parse RAM
                    $freeMem = (int) preg_replace('/[^0-9]/', '', $res['data']['freeMemory'] ?? '128');
                    $totalMem = (int) preg_replace('/[^0-9]/', '', $res['data']['totalMemory'] ?? '256');
                    if ($totalMem > 0) {
                        $ramPercent = round((($totalMem - $freeMem) / $totalMem) * 100);
                        if ($ramPercent > 80) {
                            $highRam++;
                        }
                    }
                } else {
                    $offlineRouter++;
                }
            } catch (\Exception $e) {
                $offlineRouter++;
            }

            $routerDetails[] = [
                'id' => $router->id,
                'name' => $router->name,
                'host' => $router->host,
                'status' => $status,
                'cpu_load' => $cpu,
                'ram_usage' => $ramPercent,
                'uptime' => $uptime,
            ];
        }

        // Live ISP Ping checks
        $pingGoogle = $this->pingHost('8.8.8.8');
        $pingCloudflare = $this->pingHost('1.1.1.1');
        $pingOpenDns = $this->pingHost('208.67.222.222');
        
        // Save to logs
        try {
            PingLog::create(['host' => 'Google', 'latency_ms' => $pingGoogle['latency'], 'status' => $pingGoogle['status']]);
            PingLog::create(['host' => 'Cloudflare', 'latency_ms' => $pingCloudflare['latency'], 'status' => $pingCloudflare['status']]);
            PingLog::create(['host' => 'OpenDNS', 'latency_ms' => $pingOpenDns['latency'], 'status' => $pingOpenDns['status']]);
        } catch (\Exception $e) {
            // ignore
        }

        // Get gateway ping status
        $gatewayStatus = 'green';
        $gatewayLatency = rand(5, 12); // Simulated ISP gateway ping

        return Inertia::render('NocDashboard/Index', [
            'stats' => [
                'totalRouter' => $totalRouter,
                'onlineRouter' => $onlineRouter,
                'offlineRouter' => $offlineRouter,
                'highCpu' => $highCpu,
                'highRam' => $highRam,
            ],
            'routers' => $routerDetails,
            'pings' => [
                'google' => $pingGoogle,
                'cloudflare' => $pingCloudflare,
                'opendns' => $pingOpenDns,
                'gateway' => [
                    'status' => $gatewayStatus,
                    'latency' => $gatewayLatency,
                ]
            ]
        ]);
    }

    public function getLiveStats()
    {
        // Simple API for 30s AJAX polling
        $routers = Router::where('is_active', true)->get();
        $totalRouter = $routers->count();
        $onlineRouter = 0;
        $offlineRouter = 0;
        $highCpu = 0;
        $highRam = 0;

        $connectionService = app(\App\Services\MikrotikConnectionService::class);

        $routerDetails = [];
        foreach ($routers as $router) {
            $status = 'offline';
            $cpu = 0;
            $ramPercent = 0;
            $uptime = '-';

            try {
                $res = $connectionService->testConnection($router);
                if ($res['success']) {
                    $status = 'online';
                    $onlineRouter++;
                    $cpu = (int) str_replace('%', '', $res['data']['cpuLoad'] ?? '0');
                    if ($cpu > 80) $highCpu++;

                    $uptime = $res['data']['uptime'] ?? '-';
                    $freeMem = (int) preg_replace('/[^0-9]/', '', $res['data']['freeMemory'] ?? '128');
                    $totalMem = (int) preg_replace('/[^0-9]/', '', $res['data']['totalMemory'] ?? '256');
                    if ($totalMem > 0) {
                        $ramPercent = round((($totalMem - $freeMem) / $totalMem) * 100);
                        if ($ramPercent > 80) $highRam++;
                    }
                } else {
                    $offlineRouter++;
                }
            } catch (\Exception $e) {
                $offlineRouter++;
            }

            $routerDetails[] = [
                'id' => $router->id,
                'name' => $router->name,
                'status' => $status,
                'cpu_load' => $cpu,
                'ram_usage' => $ramPercent,
                'uptime' => $uptime,
            ];
        }

        $pingGoogle = $this->pingHost('8.8.8.8');
        $pingCloudflare = $this->pingHost('1.1.1.1');
        $pingOpenDns = $this->pingHost('208.67.222.222');

        return response()->json([
            'stats' => [
                'totalRouter' => $totalRouter,
                'onlineRouter' => $onlineRouter,
                'offlineRouter' => $offlineRouter,
                'highCpu' => $highCpu,
                'highRam' => $highRam,
            ],
            'routers' => $routerDetails,
            'pings' => [
                'google' => $pingGoogle,
                'cloudflare' => $pingCloudflare,
                'opendns' => $pingOpenDns,
                'gateway' => [
                    'status' => 'green',
                    'latency' => rand(5, 12),
                ]
            ]
        ]);
    }

    public function getQueues()
    {
        $queueService = app(\App\Services\MikrotikQueueService::class);
        $queues = $queueService->getSimpleQueues();
        return response()->json($queues);
    }

    private function pingHost($host, $port = 53, $timeout = 1)
    {
        $start = microtime(true);
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if (!$fp) {
            return [
                'status' => 'red',
                'latency' => null,
            ];
        }
        fclose($fp);
        $latency = round((microtime(true) - $start) * 1000);
        return [
            'status' => $latency > 150 ? 'yellow' : 'green',
            'latency' => $latency,
        ];
    }
}
