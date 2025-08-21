<?php

namespace App\Providers;

use Http\Adapter\Guzzle7\Client as GuzzleClient;
use Illuminate\Support\ServiceProvider;
use Maclof\Kubernetes\Client;

class KubernetesClientServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        $this->app->bind(Client::class, function ($app) {
            $httpClient = GuzzleClient::createWithConfig([
                'verify' => '/var/run/secrets/kubernetes.io/serviceaccount/ca.crt',
            ]);

            return new Client([
                'master' => 'https://kubernetes.default.svc',
                'token' => '/var/run/secrets/kubernetes.io/serviceaccount/token',
            ], null, $httpClient);
        });
    }
}
