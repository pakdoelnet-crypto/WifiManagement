<?php

namespace App\Repositories\Eloquent;

use App\Models\Router;
use App\Repositories\RouterRepositoryInterface;

class RouterRepository extends BaseRepository implements RouterRepositoryInterface
{
    public function __construct(Router $model)
    {
        parent::__construct($model);
    }
}
