<?php

namespace App\Jobs;

use App\QsBatch;
use Carbon\Carbon;

class PruneQueryserviceBatchesTable extends Job
{
    public function handle()
    {
        // TODO possibly have some sort of user output...
        QsBatch::where('done', 1)
            ->where('updated_at', '<', Carbon::now()->subMonths(1))
            ->orderBy('id', 'ASC')
            ->take(50)
            ->delete();
    }
}
