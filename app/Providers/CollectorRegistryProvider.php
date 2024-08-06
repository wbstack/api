<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\Redis;
use Illuminate\Contracts\Foundation\Application;
use LKDevelopment\HorizonPrometheusExporter\Repository\ExporterRepository;


class CollectorRegistryProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(CollectorRegistry::class, function (Application $app) {
            return new CollectorRegistry(new Redis([
                'host' => Config::get('database.redis.metrics.host'),
                'port' => Config::get('database.redis.metrics.port'),
                'password' => Config::get('database.redis.metrics.password'),
                'timeout' => 0.1, // in seconds
                'read_timeout' => '10', // in seconds
                'persistent_connections' => false
            ]));
        }, true);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        ExporterRepository::setRegistry(
            $this->app->make(CollectorRegistry::class),
        );
    }
}
