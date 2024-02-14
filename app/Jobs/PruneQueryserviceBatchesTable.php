<?php

namespace App\Jobs;

use App\QsBatch;
use Carbon\Carbon;

class PruneQueryserviceBatchesTable extends Job
{
    public function __construct()
    {
        $this->onQueue(self::QUEUE_NAME_QUERYSERVICE);
    }

    public function handle(): void
    {
        QsBatch::where([
            ['done', '=', 1],
            ['pending_since', '=', null],
            ['updated_at', '<', Carbon::now()->subMonths(1)],
        ])
            ->orderBy('id', 'ASC')
            ->take(250)
            ->delete();
    }
}
