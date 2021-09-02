<?php

namespace App\Jobs;

use App\WikiDb;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use PDOException;
use App\Wiki;

/**
 * Prepends the MW Database, User with `deleted_` prefix and deletes WikiDB relation for a wiki 
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

        $wiki = Wiki::withTrashed()->where(['id' => $this->wikiId])->first();

        if( !$wiki ) {
            $this->fail(new \RuntimeException("Wiki not found for {$this->wikiId}"));
            return;
        }

        if( !$wiki->deleted_at ) {
            $this->fail(new \RuntimeException("Wiki {$this->wikiId} is not marked for deletion."));
            return;
        }

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
        $sm = $conn->getDoctrineSchemaManager();
        $tableNames = $sm->listTableNames();

        $deletedDatabaseName = 'deleted_' . $wikiDB->name;

        try {
            $pdo->beginTransaction();
            $pdo->exec('SET GLOBAL FOREIGN_KEY_CHECKS=0;');

            // Create the new database
            $pdo->exec(sprintf('CREATE DATABASE %s', $deletedDatabaseName));

            foreach($tableNames as $table) {
                $pdo->exec(sprintf('RENAME TABLE %s.%s TO %s.%s', $wikiDB->name, $table, $deletedDatabaseName, $table));
            }

            $pdo->exec(sprintf('RENAME USER %s TO %s', $wikiDB->user, 'deleted_' . $wikiDB->user ));

            $pdo->exec('SET GLOBAL FOREIGN_KEY_CHECKS=1;');
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $this->fail( new \RuntimeException('Failed to soft-soft delete '.$wikiDB->name . ': ' . $e->getMessage()) );
            return;
        }

        $wikiDB->delete();

    }
}
