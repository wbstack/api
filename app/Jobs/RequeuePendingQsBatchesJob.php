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
        $this->onQueue(self::QUEUE_NAME_QUERYSERVICE);
    }

    public function handle(): void
    {
        $failedBatches = $this->markBatchesFailed();
        foreach ($failedBatches as $batchId) {
            report("QsBatch with ID ".$batchId." was marked as failed.");
        }

        $this->requeueStalledBatches();
    }

    private function markBatchesFailed(): array
    {
        return DB::transaction(function () {
            $failedBatches = QsBatch::where([
                ['processing_attempts', '>=', $this->markFailedAfter],
                ['failed', '=', false],
                ['done', '=', false]
            ])
                ->select('id')
                ->lockForUpdate()
                ->get()
                ->pluck('id')
                ->toArray();
            QsBatch::whereIn('id', $failedBatches)->update([
                'failed' => true,
                'pending_since' => null,
            ]);
            return $failedBatches;
        }, 3);
    }

    private function requeueStalledBatches(): void
    {
        $threshold = Carbon::now()->subtract(new \DateInterval($this->pendingTimeout));
        QsBatch::where([
            ['pending_since', '<>', null],
            ['pending_since', '<', $threshold],
            ['failed', '=', false],
        ])
            ->increment(
                'processing_attempts', 1,
                ['pending_since' => null, 'done' => false]
            );
    }
}
