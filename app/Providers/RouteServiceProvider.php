<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider {
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void {
        //

        parent::boot();
    }

    /**
     * Define the routes for the application.
     * These are optionally loaded based on environment variables.
     */
    public function map(): void {
        $this->mapGeneralRoutes();
        if (getenv('ROUTES_LOAD_WEB') == 1) {
            $this->mapApiRoutes();
        }
        if (getenv('ROUTES_LOAD_SANDBOX') == 1) {
            $this->mapSandboxRoutes();
        }
        if (getenv('ROUTES_LOAD_BACKEND') == 1) {
            $this->mapBackendRoutes();
        }
    }

    protected function mapGeneralRoutes(): void {
        Route::namespace($this->namespace)
            ->group(base_path('routes/general.php'));
    }

    protected function mapApiRoutes(): void {
        Route::namespace($this->namespace)
            ->group(base_path('routes/api.php'));
    }

    protected function mapSandboxRoutes(): void {
        Route::namespace($this->namespace)
            ->group(base_path('routes/sandbox.php'));
    }

    protected function mapBackendRoutes(): void {
        Route::prefix('backend')
            ->namespace($this->namespace . '\Backend')
            ->group(base_path('routes/backend.php'));
    }
}
