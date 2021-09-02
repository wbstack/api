<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\Curl\CurlRequest;
use App\Http\Curl\HttpRequest;
use App\Jobs\ElasticSearchIndexInit;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // https://github.com/barryvdh/laravel-ide-helper
        if ($this->app->environment() !== 'production') {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }

        $this->app->bind(HttpRequest::class, CurlRequest::class);

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
