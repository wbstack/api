<?php

namespace App\Providers;

use App\Helper\DomainValidator;
use App\Rules\ForbiddenSubdomainRule;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class DomainValidatorServiceProvider extends ServiceProvider {
    /**
     * Register services.
     *
     * @return void
     */
    public function register() {
        $this->app->singleton(DomainValidator::class, function ($app) {
            $suffix = Config::get('wbstack.subdomain_suffix');

            return new DomainValidator(
                $suffix,
                [
                    new ForbiddenSubdomainRule(
                        require __DIR__ . '/../Rules/ForbiddenSubdomains.php',
                        $suffix
                    ),
                ]
            );
        });
    }
}
