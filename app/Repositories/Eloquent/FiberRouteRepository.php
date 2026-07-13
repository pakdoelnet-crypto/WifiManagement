<?php

namespace App\Repositories\Eloquent;

use App\Models\FiberRoute;
use App\Repositories\FiberRouteRepositoryInterface;

class FiberRouteRepository extends BaseRepository implements FiberRouteRepositoryInterface
{
    public function __construct(FiberRoute $model)
    {
        parent::__construct($model);
    }
}
