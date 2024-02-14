<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;

abstract class Job implements ShouldQueue
{

    public const QUEUE_NAME_PROVISIONING = 'provisioning';
    public const QUEUE_NAME_QUERYSERVICE = 'queryservice';
    public const QUEUE_NAME_CLEANUP = 'cleanup';
    public const QUEUE_NAME_STATISTICS = 'statistics';
    public const QUEUE_NAME_RECURRING = 'recurring';
    public const QUEUE_NAME_MW_JOBS = 'mw-jobs';

    /*
    |--------------------------------------------------------------------------
    | Queueable Jobs
    |--------------------------------------------------------------------------
    |
    | This job base class provides a central location to place any logic that
    | is shared across all of your jobs. The trait included with the class
    | provides access to the "queueOn" and "delay" queue helper methods.
    |
    */
    use InteractsWithQueue, Queueable, SerializesModels;

    public function backoff(): array
    {
        return Config::get('queue.backoff');
    }
}
