<?php

namespace App\Jobs;

use App\EventPageUpdate;
use Illuminate\Support\Facades\DB;
class PruneEventPageUpdatesTable extends Job {

    public function handle()
    {
        // TODO possibly have some sort of user output...
        // Assume that we don't need any event page updates here over 3 months old
        EventPageUpdate::where( 'id', '<', DB::table('event_page_updates')->max('id') - 100000 )
            ->orderBy( 'id', 'ASC' )
            ->take(50)
            ->delete();
    }

}