<?php

namespace App\Providers;

use App\Http\Curl\CurlRequest;
use App\Http\Curl\HttpRequest;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        $this->app->bind(HttpRequest::class, CurlRequest::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        Queue::failing(function (JobFailed $event): void {
            $name = data_get($event->job->payload(), 'data.commandName');
            $wrappedException = new \Exception("Executing Job '$name' failed.", 1, $event->exception);
            report($wrappedException);
        });

        // Local-only SQL query logging for debugging
        if ($this->app->environment('local')) {
            \Event::listen(QueryExecuted::class, function (QueryExecuted $query) {
                \Log::debug('Query Executed: ', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'connection' => $query->connectionName,
                ]);
            });
        }
    }
}
