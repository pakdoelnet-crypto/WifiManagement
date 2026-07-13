<?php

namespace App\Services;

use App\Repositories\PackageRepositoryInterface;
use App\Repositories\RouterRepositoryInterface;

class PackageService
{
    protected PackageRepositoryInterface $packageRepository;
    protected RouterRepositoryInterface $routerRepository;
    protected MikrotikConnectionService $connectionService;

    public function __construct(
        PackageRepositoryInterface $packageRepository,
        RouterRepositoryInterface $routerRepository,
        MikrotikConnectionService $connectionService
    ) {
        $this->packageRepository = $packageRepository;
        $this->routerRepository = $routerRepository;
        $this->connectionService = $connectionService;
    }

    public function getAllPackages()
    {
        return $this->packageRepository->all();
    }

    public function getPackageById($id)
    {
        return $this->packageRepository->find($id);
    }

    public function createPackage(array $data)
    {
        return $this->packageRepository->create($data);
    }

    public function updatePackage($id, array $data)
    {
        return $this->packageRepository->update($id, $data);
    }

    public function deletePackage($id)
    {
        return $this->packageRepository->delete($id);
    }

    public function getProfilesFromRouter($routerId)
    {
        $router = $this->routerRepository->find($routerId);
        return $this->connectionService->getProfiles($router);
    }
}
