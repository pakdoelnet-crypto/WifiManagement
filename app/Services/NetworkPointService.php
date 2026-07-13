<?php

namespace App\Services;

use App\Repositories\NetworkPointRepositoryInterface;

class NetworkPointService
{
    protected NetworkPointRepositoryInterface $repository;

    public function __construct(NetworkPointRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getAllPoints()
    {
        return $this->repository->all();
    }

    public function createPoint(array $data)
    {
        return $this->repository->create($data);
    }

    public function updatePoint($id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function deletePoint($id)
    {
        return $this->repository->delete($id);
    }
}
