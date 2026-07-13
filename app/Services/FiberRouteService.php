<?php

namespace App\Services;

use App\Repositories\FiberRouteRepositoryInterface;

class FiberRouteService
{
    protected FiberRouteRepositoryInterface $repository;

    public function __construct(FiberRouteRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getAllRoutes()
    {
        return $this->repository->all();
    }

    public function createRoute(array $data)
    {
        return $this->repository->create($data);
    }

    public function updateRoute($id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function deleteRoute($id)
    {
        return $this->repository->delete($id);
    }
}
