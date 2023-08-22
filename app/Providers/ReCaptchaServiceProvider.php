<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Rules\ReCaptchaValidation;

class ReCaptchaServiceProvider extends ServiceProvider
{
   /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(ReCaptchaValidation::class, function($app) {
            return new ReCaptchaValidation(
                config('recaptcha.secret_key'),
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
    public function provides()
    {
        return [ReCaptchaValidation::class];
    }
}
