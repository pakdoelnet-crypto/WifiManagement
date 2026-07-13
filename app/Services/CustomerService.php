<?php

namespace App\Services;

use App\Repositories\CustomerRepositoryInterface;
use App\Repositories\RouterRepositoryInterface;
use App\Repositories\PackageRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CustomerService
{
    protected CustomerRepositoryInterface $customerRepository;
    protected RouterRepositoryInterface $routerRepository;
    protected PackageRepositoryInterface $packageRepository;
    protected MikrotikConnectionService $connectionService;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        RouterRepositoryInterface $routerRepository,
        PackageRepositoryInterface $packageRepository,
        MikrotikConnectionService $connectionService
    ) {
        $this->customerRepository = $customerRepository;
        $this->routerRepository = $routerRepository;
        $this->packageRepository = $packageRepository;
        $this->connectionService = $connectionService;
    }

    public function getAllCustomers()
    {
        return $this->customerRepository->all();
    }

    public function getCustomerById($id)
    {
        return $this->customerRepository->find($id);
    }

    /**
     * Get secrets from Mikrotik that do not exist in local database.
     */
    public function getImportableSecrets($routerId)
    {
        $router = $this->routerRepository->find($routerId);
        if (!$router || !$router->is_active) {
            return [
                'success' => false,
                'error' => 'Router tidak aktif atau tidak ditemukan.'
            ];
        }

        $result = $this->connectionService->getPppSecrets($router);
        if (!$result['success']) {
            return $result;
        }

        // Get all usernames already in database for this router
        $existingUsernames = \App\Models\Customer::where('router_id', $routerId)
            ->pluck('pppoe_username')
            ->toArray();

        // Filter out secrets already imported
        $importable = array_filter($result['secrets'], function ($secret) use ($existingUsernames) {
            return !in_array($secret['name'], $existingUsernames);
        });

        return [
            'success' => true,
            'secrets' => array_values($importable)
        ];
    }

    /**
     * Import a customer from Mikrotik.
     */
    public function importCustomer(array $data)
    {
        $data['source'] = 'imported';
        $data['status'] = 'active';
        $data['joined_at'] = now()->toDateString();
        
        // Find profile mapped packages if profile matches
        if (!isset($data['package_id']) || empty($data['package_id'])) {
            $package = \App\Models\Package::where('mikrotik_profile', $data['pppoe_profile'] ?? '')->first();
            if ($package) {
                $data['package_id'] = $package->id;
            }
        }

        return $this->customerRepository->create($data);
    }

    /**
     * Add new customer (auto-creates PPPoE secret in Mikrotik).
     */
    public function createCustomer(array $data, $ktpPhoto = null, $photo = null)
    {
        // 1. Upload photos to private disk if present
        if ($ktpPhoto) {
            $ktpPath = $ktpPhoto->store('ktp', 'private');
            $data['ktp_photo_path'] = $ktpPath;
        }

        if ($photo) {
            $photoPath = $photo->store('photo', 'private');
            $data['photo_path'] = $photoPath;
        }

        $data['source'] = 'created_by_app';
        $data['status'] = 'active';
        $data['joined_at'] = now()->toDateString();

        // 2. Provision secret in Mikrotik
        $router = $this->routerRepository->find($data['router_id']);
        $package = $this->packageRepository->find($data['package_id']);

        if ($router && $router->is_active && $package) {
            $secretId = $this->connectionService->addPppSecret($router, [
                'name' => $data['pppoe_username'],
                'password' => $data['pppoe_password'],
                'profile' => $package->mikrotik_profile,
            ]);

            if ($secretId) {
                $data['pppoe_secret_id'] = $secretId;
            } else {
                throw new \Exception('Gagal membuat PPPoE secret di router MikroTik.');
            }
        } else {
            throw new \Exception('Router tidak aktif atau tidak ditemukan.');
        }

        // 3. Save locally
        return $this->customerRepository->create($data);
    }

    /**
     * Update customer.
     */
    public function updateCustomer($id, array $data, $ktpPhoto = null, $photo = null)
    {
        $customer = $this->customerRepository->find($id);

        // Upload photos to private disk
        if ($ktpPhoto) {
            if ($customer->ktp_photo_path) {
                Storage::disk('private')->delete($customer->ktp_photo_path);
            }
            $ktpPath = $ktpPhoto->store('ktp', 'private');
            $data['ktp_photo_path'] = $ktpPath;
        }

        if ($photo) {
            if ($customer->photo_path) {
                Storage::disk('private')->delete($customer->photo_path);
            }
            $photoPath = $photo->store('photo', 'private');
            $data['photo_path'] = $photoPath;
        }

        $oldRouterId = $customer->router_id;
        $oldSecretId = $customer->pppoe_secret_id;
        $oldUsername = $customer->pppoe_username;

        // Provision secret updates in Mikrotik
        $newRouterId = $data['router_id'];
        $package = $this->packageRepository->find($data['package_id']);
        $routerChanged = ($oldRouterId != $newRouterId);

        if ($routerChanged) {
            // Remove from old router
            if ($oldRouterId && $oldSecretId) {
                $oldRouter = $this->routerRepository->find($oldRouterId);
                if ($oldRouter && $oldRouter->is_active) {
                    $this->connectionService->deletePppSecret($oldRouter, $oldSecretId);
                }
            }

            // Create on new router
            $newRouter = $this->routerRepository->find($newRouterId);
            if ($newRouter && $newRouter->is_active && $package) {
                $newSecretId = $this->connectionService->addPppSecret($newRouter, [
                    'name' => $data['pppoe_username'],
                    'password' => $data['pppoe_password'] ?? $customer->pppoe_password,
                    'profile' => $package->mikrotik_profile,
                ]);

                if ($newSecretId) {
                    $data['pppoe_secret_id'] = $newSecretId;
                } else {
                    throw new \Exception('Gagal membuat PPPoE secret di router MikroTik yang baru.');
                }
            }
        } else {
            // Update same router
            $router = $this->routerRepository->find($newRouterId);
            if ($router && $router->is_active && $oldSecretId && $package) {
                $this->connectionService->updatePppSecret($router, $oldSecretId, [
                    'name' => $data['pppoe_username'],
                    'password' => $data['pppoe_password'] ?? $customer->pppoe_password,
                    'profile' => $package->mikrotik_profile,
                ]);
            }
        }

        // Save local DB
        return $this->customerRepository->update($id, $data);
    }

    /**
     * Delete customer and remove their secret.
     */
    public function deleteCustomer($id)
    {
        $customer = $this->customerRepository->find($id);

        // Delete files from private disk
        if ($customer->ktp_photo_path) {
            Storage::disk('private')->delete($customer->ktp_photo_path);
        }
        if ($customer->photo_path) {
            Storage::disk('private')->delete($customer->photo_path);
        }

        // Remove from Mikrotik
        if ($customer->router_id && $customer->pppoe_secret_id) {
            $router = $this->routerRepository->find($customer->router_id);
            if ($router && $router->is_active) {
                $this->connectionService->deletePppSecret($router, $customer->pppoe_secret_id);
            }
        }

        return $this->customerRepository->delete($id);
    }

    /**
     * Toggle active/disable status.
     */
    public function toggleStatus($id, string $status)
    {
        $customer = $this->customerRepository->update($id, ['status' => $status]);

        if ($customer->router_id && $customer->pppoe_secret_id) {
            $router = $this->routerRepository->find($customer->router_id);
            if ($router && $router->is_active) {
                $enable = ($status === 'active');
                $this->connectionService->togglePppSecret($router, $customer->pppoe_secret_id, $enable);
            }
        }

        return $customer;
    }
}
