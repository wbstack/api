<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;

enum Queue: string {
    case Provisioning = 'provisioning';
    case Queryservice = 'queryservice';
    case Cleanup = 'cleanup';
    case Statistics = 'statistics';
    case Recurring = 'recurring';
    case MediawikiJobs = 'mw-jobs';
}

abstract class Job implements ShouldQueue
{
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
    use InteractsWithQueue, SerializesModels;
    use Queueable {
        onQueue as protected parentOnQueue;
    }

    public function backoff(): array
    {
        return Config::get('queue.backoff');
    }

    public function onQueue(Queue|string $queue)
    {
        if (is_string($queue)) {
            $this->parentOnQueue($queue);
            return;
        }
        $this->parentOnQueue($queue->value);
    }
}
