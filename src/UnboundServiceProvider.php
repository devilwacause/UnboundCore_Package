<?php

namespace Devilwacause\UnboundCore;

use Devilwacause\UnboundCore\Http\{
    Interfaces\FolderRepositoryInterface,
    Interfaces\ImageRepositoryInterface,
    Interfaces\FileRepositoryInterface,

    Repositories\FolderRepository,
    Repositories\ImageRepository,
    Repositories\FileRepository};

use Illuminate\Support\Facades\ {
    Validator,
    Route
};

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class UnboundServiceProvider extends BaseServiceProvider
{
    public function register()
    {

    }

    public function boot()
    {
        $this->bindRepositories();

        $this->loadMigrations();
        $this->activateObservers();
        $this->registerWebRoutes();
        $this->registerApiRoutes();
        $this->moveConfigs();
        $this->moveTests();
        $this->activateValidators();
    }

    protected function bindRepositories() {
        $this->app->bind(
            ImageRepositoryInterface::class,
            ImageRepository::class
        );
        $this->app->bind(
            FileRepositoryInterface::class,
            FileRepository::class
        );
        $this->app->bind(
            FolderRepositoryInterface::class,
            FolderRepository::class
        );
    }

    protected function activateValidators() {
        Validator::extend(
            'base64image',
            '\Devilwacause\UnboundCore\Validators\Base64Validation@validateBase64Image'
        );
    }

    protected function loadMigrations()
    {
        try {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }catch(\Exception $e) {
            dd($e);
        }
    }

    protected function activateObservers()
    {
        \Devilwacause\UnboundCore\Models\Image::observe(
            \Devilwacause\UnboundCore\Observers\ImageObserver::class);
    }

    protected function registerWebRoutes()
    {
        Route::group($this->webRouteConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }

    protected function registerApiRoutes()
    {
        Route::group($this->apiRouteConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ .'/../routes/api.php');
        });
    }

    protected function moveConfigs() {
        $this->publishes([
            __DIR__ . '/../config/config.php' => config_path('unbound.php'),
            __DIR__ . '/../config/glide.php' => config_path('glide.php')
        ]);

        $this->mergeConfigFrom(
            __DIR__ . '/../config/logging.php', 'logging.channels'
        );
    }

    protected function moveTests() {
        $this->publishes([
            __DIR__ . '/../tests/Feature' => public_path('../tests/Unbound')
        ]);
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