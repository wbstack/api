<?php

namespace App\Jobs;

class FailJob extends Job {
    public function handle(): void {
        $this->fail(self::class . ': The job failed successfully (this is intended, as this is a test Job). Have a nice day.');
    }
}
