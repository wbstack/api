<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Config;

abstract class Job implements ShouldQueue {
    use Queueable;

    public $timeout = 60;

    public function backoff(): array {
        return Config::get('queue.backoff');
    }
}
