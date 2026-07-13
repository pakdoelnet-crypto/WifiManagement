<?php

namespace App\Services;

use App\Repositories\RouterRepositoryInterface;

class RouterService
{
    protected RouterRepositoryInterface $routerRepository;
    protected MikrotikConnectionService $connectionService;

    public function __construct(
        RouterRepositoryInterface $routerRepository,
        MikrotikConnectionService $connectionService
    ) {
        $this->routerRepository = $routerRepository;
        $this->connectionService = $connectionService;
    }

    public function getAllRouters()
    {
        return $this->routerRepository->all();
    }

    public function getRouterById($id)
    {
        return $this->routerRepository->find($id);
    }

    public function createRouter(array $data)
    {
        return $this->routerRepository->create($data);
    }

    public function updateRouter($id, array $data)
    {
        if (empty($data['password'])) {
            unset($data['password']);
        }
        return $this->routerRepository->update($id, $data);
    }

    public function deleteRouter($id)
    {
        return $this->routerRepository->delete($id);
    }

    public function testConnection($id)
    {
        $router = $this->getRouterById($id);
        $result = $this->connectionService->testConnection($router);

        $status = $result['success'] ? 'online' : 'offline';
        $this->routerRepository->update($id, [
            'status' => $status,
            'last_checked_at' => now(),
        ]);

        return $result;
    }
}
