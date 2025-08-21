<?php

namespace App\Providers;

use App\Rules\ReCaptchaValidation;
use Illuminate\Support\ServiceProvider;
use ReCaptcha\ReCaptcha;

class ReCaptchaServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        $this->app->bind(ReCaptchaValidation::class, function ($app) {
            $recaptcha = new ReCaptcha(
                config('recaptcha.secret_key')
            );

            return new ReCaptchaValidation(
                $recaptcha,
                config('recaptcha.min_score'),
                config('app.url')
            );
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() {
        return [ReCaptchaValidation::class];
    }
}
