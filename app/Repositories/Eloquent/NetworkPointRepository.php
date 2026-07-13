<?php

namespace App\Repositories\Eloquent;

use App\Models\NetworkPoint;
use App\Repositories\NetworkPointRepositoryInterface;

class NetworkPointRepository extends BaseRepository implements NetworkPointRepositoryInterface
{
    public function __construct(NetworkPoint $model)
    {
        parent::__construct($model);
    }
}
