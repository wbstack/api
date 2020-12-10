<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
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
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
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

    protected function mapGeneralRoutes()
    {
        Route::namespace($this->namespace)
             ->group(base_path('routes/general.php'));
    }

    protected function mapApiRoutes()
    {
        Route::namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }

    protected function mapSandboxRoutes()
    {
        Route::namespace($this->namespace)
             ->group(base_path('routes/sandbox.php'));
    }

    protected function mapBackendRoutes()
    {
        Route::prefix('backend')
             ->namespace($this->namespace.'\Backend')
             ->group(base_path('routes/backend.php'));
    }
}
