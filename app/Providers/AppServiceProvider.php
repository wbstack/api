<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobFailed;
use App\Http\Curl\CurlRequest;
use App\Http\Curl\HttpRequest;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(HttpRequest::class, CurlRequest::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Queue::failing(function (JobFailed $event) {
            $name = data_get($event->job->payload(), 'data.commandName');
            $wrappedException = new \Exception("Executing Job '$name' failed.", 1, $event->exception);
            report($wrappedException);
        });
    }
}
