<?php

namespace App\Jobs;

use App\EventPageUpdate;
use Carbon\Carbon;

class PruneEventPageUpdatesTable extends Job {

    public function handle()
    {
        // TODO possibly have some sort of user output...
        // Assume that we don't need any event page updates here over 3 months old
        EventPageUpdate::where( 'done',1 )
            ->where( 'updated_at', '<', Carbon::now()->subMonths(3) )
            ->orderBy( 'id', 'ASC' )
            ->take(50)
            ->delete();
    }

}
