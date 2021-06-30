<?php

namespace App\Jobs;

use App\EventPageUpdate;
use Illuminate\Support\Facades\DB;

class PruneEventPageUpdatesTable extends Job
{
    public function handle(): void
    {
        // Assume that we only need the latest 100k page update events
        // and delete 100 if there are too many
        EventPageUpdate::where('id', '<', DB::table('event_page_updates')->max('id') - 100000)
            ->orderBy('id', 'ASC')
            ->take(100)
            ->delete();
    }
}
