<?php

namespace App\Services;

use App\Models\Router;
use RouterOS\Client;
use RouterOS\Config;
use RouterOS\Query;

class MikrotikConnectionService
{
    /**
     * Get a configured and connected RouterOS client instance.
     *
     * @param Router $router
     * @return Client
     */
    public function getClient(Router $router): Client
    {
        $config = new Config([
            'host' => $router->host,
            'port' => (int) $router->port,
            'user' => $router->username,
            'pass' => $router->password,
            'ssl' => $router->connection_type === 'api_ssl',
            'ssl_options' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
            'timeout' => 5, // 5 seconds timeout
            'throw_timeout_exception' => false, // Fix "Stream timed out" bug on Windows/PHP tunnels
        ]);

        return new Client($config);
    }

    /**
     * Test connection and retrieve system resources.
     *
     * @param Router $router
     * @return array
     */
    public function testConnection(Router $router): array
    {
        try {
            $client = $this->getClient($router);
            
            // Query /system/resource/print
            $query = new Query('/system/resource/print');
            $response = $client->query($query)->read();

            if (is_array($response) && isset($response[0])) {
                $data = $response[0];
                return [
                    'success' => true,
                    'data' => [
                        'cpuLoad' => isset($data['cpu-load']) ? $data['cpu-load'] . '%' : 'N/A',
                        'uptime' => $data['uptime'] ?? 'N/A',
                        'boardName' => $data['board-name'] ?? 'N/A',
                        'version' => $data['version'] ?? 'N/A',
                        'freeMemory' => isset($data['free-memory']) ? round($data['free-memory'] / 1024 / 1024, 1) . ' MB' : 'N/A',
                        'totalMemory' => isset($data['total-memory']) ? round($data['total-memory'] / 1024 / 1024, 1) . ' MB' : 'N/A',
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => 'Invalid or empty response from RouterOS API.'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Fetch all interfaces from the router.
     */
    public function getInterfaces(Router $router): array
    {
        try {
            $client = $this->getClient($router);
            $response = $client->query('/interface/print')->read();

            if (is_array($response)) {
                return [
                    'success' => true,
                    'interfaces' => array_map(function ($item) {
                        return [
                            'name' => $item['name'] ?? '',
                            'type' => $item['type'] ?? '',
                            'running' => ($item['running'] ?? 'false') === 'true',
                            'disabled' => ($item['disabled'] ?? 'false') === 'true',
                        ];
                    }, $response)
                ];
            }

            return [
                'success' => false,
                'error' => 'Gagal membaca interface dari MikroTik.'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get real-time traffic statistics for a specific interface.
     */
    public function getInterfaceTraffic(Router $router, string $interfaceName): array
    {
        try {
            $client = $this->getClient($router);
            $query = new Query('/interface/monitor-traffic', [
                '=interface=' . $interfaceName,
                '=once=' => '',
            ]);
            $response = $client->query($query)->read();

            if (is_array($response) && isset($response[0])) {
                $data = $response[0];
                return [
                    'success' => true,
                    'data' => [
                        'rx' => (int) ($data['rx-bits-per-second'] ?? 0),
                        'tx' => (int) ($data['tx-bits-per-second'] ?? 0),
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => 'Gagal memantau trafik interface.'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Fetch all PPP profiles from the router.
     *
     * @param Router $router
     * @return array
     */
    public function getProfiles(Router $router): array
    {
        try {
            $client = $this->getClient($router);
            $response = $client->query('/ppp/profile/print')->read();

            if (is_array($response)) {
                return [
                    'success' => true,
                    'profiles' => array_map(function ($item) {
                        return [
                            'name' => $item['name'] ?? '',
                            'rate_limit' => $item['rate-limit'] ?? 'No Limit',
                            'local_address' => $item['local-address'] ?? '',
                            'remote_address' => $item['remote-address'] ?? '',
                        ];
                    }, $response)
                ];
            }

            return [
                'success' => false,
                'error' => 'Gagal membaca profil dari MikroTik.'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Add a PPP secret to the router.
     */
    public function addPppSecret(Router $router, array $secretData): ?string
    {
        try {
            $client = $this->getClient($router);
            $query = new Query('/ppp/secret/add', [
                '=name=' . $secretData['name'],
                '=password=' . $secretData['password'],
                '=profile=' . $secretData['profile'],
                '=service=pppoe',
            ]);
            $response = $client->query($query)->read();

            if (is_array($response)) {
                if (isset($response[0]['.id'])) {
                    return $response[0]['.id'];
                }
                if (isset($response['after']['ret'])) {
                    return $response['after']['ret'];
                }
            }
            return null;
        } catch (\Exception $e) {
            \Log::error('Mikrotik addPppSecret error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update a PPP secret on the router.
     */
    public function updatePppSecret(Router $router, string $secretId, array $secretData): bool
    {
        try {
            $client = $this->getClient($router);
            $query = new Query('/ppp/secret/set', [
                '=.id=' . $secretId,
                '=name=' . $secretData['name'],
                '=password=' . $secretData['password'],
                '=profile=' . $secretData['profile'],
            ]);
            $client->query($query)->read();
            return true;
        } catch (\Exception $e) {
            \Log::error('Mikrotik updatePppSecret error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a PPP secret from the router.
     */
    public function deletePppSecret(Router $router, string $secretId): bool
    {
        try {
            $client = $this->getClient($router);
            $query = new Query('/ppp/secret/remove', [
                '=.id=' . $secretId,
            ]);
            $client->query($query)->read();
            return true;
        } catch (\Exception $e) {
            \Log::error('Mikrotik deletePppSecret error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Enable/disable a PPP secret on the router.
     */
    public function togglePppSecret(Router $router, string $secretId, bool $enable): bool
    {
        try {
            $client = $this->getClient($router);
            $query = new Query('/ppp/secret/set', [
                '=.id=' . $secretId,
                '=disabled=' . ($enable ? 'no' : 'yes'),
            ]);
            $client->query($query)->read();
            return true;
        } catch (\Exception $e) {
            \Log::error('Mikrotik togglePppSecret error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch all PPP secrets from the router.
     */
    public function getPppSecrets(Router $router): array
    {
        try {
            $client = $this->getClient($router);
            $response = $client->query('/ppp/secret/print')->read();

            if (is_array($response)) {
                return [
                    'success' => true,
                    'secrets' => array_map(function ($item) {
                        return [
                            'id' => $item['.id'] ?? '',
                            'name' => $item['name'] ?? '',
                            'password' => $item['password'] ?? '',
                            'profile' => $item['profile'] ?? '',
                            'service' => $item['service'] ?? '',
                            'disabled' => ($item['disabled'] ?? 'false') === 'true',
                            'comment' => $item['comment'] ?? '',
                        ];
                    }, $response)
                ];
            }

            return [
                'success' => false,
                'error' => 'Gagal membaca secrets dari MikroTik.'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Fetch all active PPP sessions from the router.
     */
    public function getPppActiveSessions(Router $router): array
    {
        try {
            $client = $this->getClient($router);
            $response = $client->query('/ppp/active/print')->read();

            if (is_array($response)) {
                return [
                    'success' => true,
                    'sessions' => array_map(function ($item) {
                        return [
                            'id' => $item['.id'] ?? '',
                            'name' => $item['name'] ?? '',
                            'address' => $item['address'] ?? '',
                            'uptime' => $item['uptime'] ?? '',
                            'caller_id' => $item['caller-id'] ?? '',
                        ];
                    }, $response)
                ];
            }

            return [
                'success' => false,
                'error' => 'Gagal membaca active sessions dari MikroTik.'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Disconnect an active PPP session by username.
     */
    public function disconnectPppSession(Router $router, string $username): bool
    {
        try {
            $client = $this->getClient($router);
            
            // Find active session ID
            $query = new Query('/ppp/active/print', [['name', '=', $username]]);
            $response = $client->query($query)->read();

            if (is_array($response) && isset($response[0]['.id'])) {
                $sessionId = $response[0]['.id'];
                
                // Remove the session
                $removeQuery = new Query('/ppp/active/remove', ['=.id=' . $sessionId]);
                $client->query($removeQuery)->read();
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            \Log::error("Mikrotik disconnectPppSession error for {$username}: " . $e->getMessage());
            return false;
        }
    }
}
