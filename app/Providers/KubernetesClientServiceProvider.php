<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Maclof\Kubernetes\Client;

class KubernetesClientServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Client::class , function ($app) {
            return new Client([
            'master' => 'https://kubernetes.default.svc',
            'ca_cert' => '/var/run/secrets/kubernetes.io/serviceaccount/ca.crt',
            'token' => '/var/run/secrets/kubernetes.io/serviceaccount/token',
            ]);
        });
    }
}