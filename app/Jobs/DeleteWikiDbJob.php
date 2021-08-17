<?php

namespace App\Jobs;

use App\WikiDb;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldBeUnique;

/**
 * Deletes the created Database, User and WikiDB relation for a wiki 
 */
class DeleteWikiDbJob extends Job implements ShouldBeUnique
{
    private $wikiId;

    /**
     * @return void
     */
    public function __construct( int $wikiId )
    {
        $this->wikiId = $wikiId;
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return strval($this->wikiId);
    }

    /**
     * @return void
     */
    public function handle()
    {
        $wikiDB = WikiDb::whereWikiId( $this->wikiId )->first();

        if( !$wikiDB ) {
            $this->fail(new \RuntimeException("WikiDb not found for {$this->wikiId}"));
            return;
        }

        $conn = DB::connection('mw');
        if (! $conn instanceof \Illuminate\Database\Connection) {
            $this->fail(new \RuntimeException('Must be run on a PDO based DB connection'));
            return;
        }
        $pdo = $conn->getPdo();

        // DELETE the database
        if ($pdo->exec('DROP DATABASE '.$wikiDB->name) === false) {
            $this->fail( new \RuntimeException('Failed to create database with dbname: '.$wikiDB->name) );
            // let it pass through and try to delete the user too
        }

        if ($pdo->exec('DROP USER '.$wikiDB->user) === false) {
            $this->fail( new \RuntimeException('Failed to delete database with dbname: '.$wikiDB->name) );
            return;
        }

        $wikiDB->delete();
    }
}
