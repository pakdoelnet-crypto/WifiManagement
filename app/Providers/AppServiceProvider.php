<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\UserRepositoryInterface::class,
            \App\Repositories\Eloquent\UserRepository::class
        );
        $this->app->bind(
            \App\Repositories\RouterRepositoryInterface::class,
            \App\Repositories\Eloquent\RouterRepository::class
        );
        $this->app->bind(
            \App\Repositories\PackageRepositoryInterface::class,
            \App\Repositories\Eloquent\PackageRepository::class
        );
        $this->app->bind(
            \App\Repositories\CustomerRepositoryInterface::class,
            \App\Repositories\Eloquent\CustomerRepository::class
        );
        $this->app->bind(
            \App\Repositories\NetworkPointRepositoryInterface::class,
            \App\Repositories\Eloquent\NetworkPointRepository::class
        );
        $this->app->bind(
            \App\Repositories\FiberRouteRepositoryInterface::class,
            \App\Repositories\Eloquent\FiberRouteRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
