<?php

namespace App\Jobs;

use App\WikiDb;
use App\QueryserviceNamespace;

class EnsureStoragePoolsPopulatedJob extends Job
{
    private $requiredDbs = 10;

    /**
     * @return void
     */
    public function handle()
    {
        // TODO these these bits should enter rows that say PENDING?!
        // then if rows say pending trigger a job to create them? :)
        // This avoid these jobs running away and creating things if they then fail to insert records into the sql..

        // Wiki Dbs
        $unassignedDbs = WikiDb::where('wiki_id', null)->count();
        $toCreate = 10 - $unassignedDbs;
        if ($toCreate > 0) {
            for ($i = 0; $i < $toCreate; $i++) {
                dispatch(new ProvisionWikiDbJob());
            }
        }

        // Query service namespaces
        $unassignedQueryserviceNamespaces = QueryserviceNamespace::where('wiki_id', null)->count();
        $toCreate = 10 - $unassignedQueryserviceNamespaces;
        if ($toCreate > 0) {
            for ($i = 0; $i < $toCreate; $i++) {
                dispatch(new ProvisionQueryserviceNamespaceJob());
            }
        }
    }
}
