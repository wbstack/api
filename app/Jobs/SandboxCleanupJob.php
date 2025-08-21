<?php

namespace App\Jobs;

use App\Wiki;
use App\WikiSetting;

class SandboxCleanupJob extends Job {
    public function handle(): void {
        Wiki::whereIn('id', WikiSetting::whereName('wwSandboxAutoUserLogin')->pluck('wiki_id')->toArray())
            ->where('created_at', '<', date('Y-m-d', strtotime('-1 week')))->delete();
    }
}
