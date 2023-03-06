<?php

namespace Devilwacause\UnboundCore;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
class UnboundServiceProvider extends ServiceProvider
{
    public function register()
    {

    }

    public function boot()
    {
        $this->loadMigrations();
        $this->activateObservers();
        $this->registerWebRoutes();
        $this->registerApiRoutes();
    }


    protected function loadMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function activateObservers()
    {
        \Devilwacause\UnboundCore\Models\Image::observe(\Devilwacause\UnboundCore\Observers\ImageObserver::class);
    }

    protected function registerWebRoutes()
    {
        Route::group($this->webRouteConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        });
    }

    protected function registerApiRoutes()
    {
        Route::group($this->apiRouteConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ .'/../routes/web.php');
        });
    }

    protected function webRouteConfiguration()
    {
        return [
            'prefix' => config('unbound.ROUTE_PREFIX'),
            'middleware' => config('unbound.ROUTE_MIDDLEWARE')
        ];
    }

    protected function apiRouteConfiguration()
    {
        return [
            'prefix' => config('unbound.API_ROUTE_PREFIX'),
            'middleware' => config('unbound.API_ROUTE_MIDDLEWARE')
        ];
    }
}