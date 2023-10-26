<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Config;
use App\QsBatch;
use Carbon\Carbon;

class RequeuePendingBatches extends Job
{
    private $pendingTimeout;
    public function __construct() {
        $this->pendingTimeout = Config::get('wbstack.qs_batch_pending_timeout');
    }

    public function handle(): void
    {
        $threshold = Carbon::now()->subtract(new \DateInterval($this->pendingTimeout));
        QsBatch::where([['pending_since', '<>', null], ['pending_since', '<', $threshold]])
            ->update(['pending_since' => null, 'done' => 0]);
    }
}
