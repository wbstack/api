<?php

namespace App\Jobs;

use App\WikiDb;
use Illuminate\Support\Facades\DB;

/**
 * This can be run with for example:
 * php artisan job:handle UpdateWikiDbJob id,1,mw1.31-oc1,mw1.31-oc2 ,.
 *
 * If you wantedto be wreckless and pick any wiki:
 * php artisan job:handle UpdateWikiDbJob version,mw1.31-oc1,mw1.31-oc1,mw1.31-oc2 ,
 */
class UpdateWikiDbJob extends Job
{
    private $selectCol;

    private $selectValue;

    private $from;

    private $to;

    private $updateFilePath;

    /**
     * @return void
     */
    public function __construct($selectCol, $selectValue, $from, $to)
    {
        $this->selectCol = $selectCol;
        $this->selectValue = $selectValue;
        $this->from = $from;
        $this->to = $to;
        $updateFileName = $from.'_to_'.$to.'.sql';
        $updateFilePath = __DIR__.'/../../database/mw/updates/'.$updateFileName;
        if (! file_exists($updateFilePath)) {
            throw new \InvalidArgumentException('Can not find a way to update between specified versions.');
        }
        $this->updateFilePath = $updateFilePath;
    }

    /**
     * @return void
     */
    public function handle()
    {
        // Get the Wikidb we are operating on
        $wikidb = WikiDb::where($this->selectCol, $this->selectValue)->firstOrFail();

        // Make sure the wikidb is at the expected level
        if ($wikidb->version !== $this->from) {
            throw new \RuntimeException(
          'Wiki Db selected is at different version than expected. '.
          'At: '.$wikidb->version.' Expected: '.$this->from
        );
        }

        // Get SQL statements to run
        $rawSql = file_get_contents($this->updateFilePath);
        $prefixedSql = str_replace('<<prefix>>', $wikidb->prefix, $rawSql);
        $sqlParts = explode("\n\n", $prefixedSql);

        // Connect to the mediawiki server
        $pdo = DB::connection('mw')->getPdo();

        foreach ($sqlParts as $part) {
            if (strpos($part, '--') === 0) {
                // Skip comment blocks
                continue;
            }
            // Execute each chunk of SQL...
            if ($pdo->exec($part) === false) {
                throw new \RuntimeException(
            'SQL execution failed, SQL part: '.$part);
            }
        }

        $wikidb->version = $this->to;
        $wikidb->save();

        // TODO log? Do something?
    }
}
