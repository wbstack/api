<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
      // TODO SHIFT is this needed? I see the commands() function below
      // that might do automagic loading?
      \App\Console\Commands\DispatchJob::class,
      \App\Console\Commands\HandleJob::class,
    ];

    // https://laravel.com/docs/5.8/middleware#assigning-middleware-to-routes
    protected $routeMiddleware = [
          'cors' => App\Http\Middleware\CorsMiddleware::class,
          'backend.auth' => App\Http\Middleware\BackendAuth::class,
          'throttle' => App\Http\Middleware\ThrottleRequests::class,
          'admin' => App\Http\Middleware\AdminMiddleware::class,
          'auth' => App\Http\Middleware\Authenticate::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
      $schedule->job(new EnsureWikiDbPoolPopulatedJob)->everyMinute();
      $schedule->job(new ExpireOldUserVerificationTokensJob)->hourly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
