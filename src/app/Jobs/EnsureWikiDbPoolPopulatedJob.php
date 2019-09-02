<?php

namespace App\Jobs;

use App\WikiDb;

class EnsureWikiDbPoolPopulatedJob extends Job
{
    private $requiredDbs = 10;

    /**
     * @return void
     */
    public function handle()
    {
        $unassignedDbs = WikiDb::where('wiki_id', null)->count();
        $toCreate = 10 - $unassignedDbs;
        if ($toCreate > 0) {
            for ($i = 0; $i < $toCreate; $i++) {
                dispatch(new ProvisionWikiDbJob());
            }
        }
    }
}
