<?php

namespace App\Jobs;

use App\WikiDb;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use PDOException;
use App\Wiki;
use Carbon\Carbon;

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

        $timestamp = Carbon::now()->timestamp;
        $deletedDatabaseName = "deleted_{$timestamp}_{$this->wikiId}";

        try {
            $pdo->beginTransaction();

            // Create the new database
            $pdo->exec(sprintf('CREATE DATABASE %s', $deletedDatabaseName));

            // iterate over each table and replace the prefix
            // and move it to the deleted database by using RENAME TABLE
            foreach($tableNames as $table) {
                $replacedCount = 0;
                $tableWithoutPrefix = str_replace($wikiDB->prefix . '_', '', $table, $replacedCount );
                if ($replacedCount !== 1) {
                    throw new \RuntimeException("Did not find prefix '{$wikiDB->prefix}' in tablename '{$table}' ");
                }
                $pdo->exec(sprintf('RENAME TABLE %s.%s TO %s.%s', $wikiDB->name, $table, $deletedDatabaseName, $tableWithoutPrefix));
            }

            $pdo->exec(sprintf('DROP USER %s', $wikiDB->user ));

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $this->fail( new \RuntimeException('Failed to soft-soft delete '.$wikiDB->name . ': ' . $e->getMessage()) );
            return;
        }

        $wikiDB->delete();

    }
}
