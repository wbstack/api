<?php

namespace App\Jobs;

use App\WikiDb;

/**
 * This can be run with for example:
 * php artisan job:handle ScheduleUpdateWikiDbJobs id,1,mw1.31-oc1,mw1.31-oc2 ,
 *
 * If you wanted to be wreckless and schedule for all of the wikis:
 * php artisan job:handle ScheduleUpdateWikiDbJobs version,mw1.31-oc1,mw1.31-oc1,mw1.31-oc2 ,
 */
class ScheduleUpdateWikiDbJobs extends Job
{
    private $selectCol;

    private $selectValue;

    private $from;

    private $to;

    /**
     * @return void
     */
    public function __construct($selectCol, $selectValue, $from, $to)
    {
        $this->selectCol = $selectCol;
        $this->selectValue = $selectValue;
        $this->from = $from;
        $this->to = $to;
        // TODO logic of db update files should be kept somewhere...
        $updateFileName = $from.'_to_'.$to.'.sql';
        $updateFilePath = __DIR__.'/../../database/mw/updates/'.$updateFileName;
        if (! file_exists($updateFilePath)) {
            throw new \InvalidArgumentException('Can not find a way to update between specified versions.');
        }
    }

    /**
     * @return void
     */
    public function handle()
    {
        // Get the Wikidbs we are operating on
		// TODO one day this will want to be batched?
        $wikidbs = WikiDb::where($this->selectCol, $this->selectValue)->get();

        // And schedule a job for each of them
        foreach( $wikidbs as $wikidb ) {
			dispatch(new UpdateWikiDbJob('id', $wikidb->id, $this->from, $this->to));
		}
    }
}
