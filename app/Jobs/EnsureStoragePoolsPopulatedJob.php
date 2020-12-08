<?php

namespace App\Jobs;

use App\WikiDb;
use App\QueryserviceNamespace;

/**
 * This job runs on a cron and ensures the pools are filled that are needed for fast wiki creation
 *
 * Example usage:
 * php artisan wbs-job:handle EnsureStoragePoolsPopulatedJob
 */
class EnsureStoragePoolsPopulatedJob extends Job
{
    private $requiredDbs = 10;
    private $requiredQueryServiceNS = 10;

    /**
     * @return void
     */
    public function handle()
    {
        // TODO these these bits should enter rows that say PENDING?!
        // then if rows say pending trigger a job to create them? :)
        // This avoid these jobs running away and creating things if they then fail to insert records into the sql..

        // Wiki Dbs
        $wikiDbCondition = ['wiki_id'=>null,'version'=>'mw1.35-wbs1'];
        $unassignedDbs = WikiDb::where($wikiDbCondition)->count();
        $toCreate = $this->requiredDbs - $unassignedDbs;
        if ($toCreate > 0) {
            for ($i = 0; $i < $toCreate; $i++) {
                dispatch(new ProvisionWikiDbJob());
            }
        }

        // Query service namespaces
        $unassignedQueryserviceNamespaces = QueryserviceNamespace::where('wiki_id', null)->count();
        $toCreate = $this->requiredQueryServiceNS - $unassignedQueryserviceNamespaces;
        if ($toCreate > 0) {
            for ($i = 0; $i < $toCreate; $i++) {
                dispatch(new ProvisionQueryserviceNamespaceJob());
            }
        }
    }

}
