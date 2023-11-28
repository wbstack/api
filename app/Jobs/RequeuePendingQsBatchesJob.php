<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\QsBatch;
use Carbon\Carbon;

class RequeuePendingQsBatchesJob extends Job
{
    private $pendingTimeout;
    private $markFailedAfter;
    public function __construct() {
        $this->pendingTimeout = Config::get('wbstack.qs_batch_pending_timeout');
        $this->markFailedAfter = Config::get('wbstack.qs_batch_mark_failed_after');
    }

    public function handle(): void
    {
        $failedBatches = [];
        DB::transaction(function () use (&$failedBatches) {
            $failedBatches = QsBatch::where([
                ['processing_attempts', '>=', $this->markFailedAfter],
                ['failed', '=', false],
            ])->get()->pluck('id');
            QsBatch::whereIn('id', $failedBatches)->update(['failed' => true]);
        });
        foreach ($failedBatches as $batchId) {
            report("QsBatch with ID ".$batchId."was marked as failed.");
        }

        $threshold = Carbon::now()->subtract(new \DateInterval($this->pendingTimeout));
        QsBatch::where([['pending_since', '<>', null], ['pending_since', '<', $threshold]])
            ->increment(
                'processing_attempts', 1,
                ['pending_since' => null, 'done' => 0]
            );
    }
}
