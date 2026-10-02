<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Config;

abstract class Job implements ShouldQueue {
    use Queueable;

    // Set the execution timeout in seconds for jobs extending this class.
    // This value takes precedence over the default timeout provided by the queue worker.
    // Individual jobs may override this value.
    // https://laravel.com/framework/docs/11.x/queues#timeout
    // The timeout should be several seconds shorter than the queue connection's `retry_after`
    // value. Otherwise, the job may be released back onto the queue, where another queue worker
    // can attempt it, before the original has finished executing or timed out.
    // https://laravel.com/framework/docs/11.x/queues#job-expirations-and-timeouts
    public $timeout = 60;

    public function backoff(): array {
        return Config::get('queue.backoff');
    }
}
