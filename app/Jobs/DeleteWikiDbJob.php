<?php

namespace App\Jobs;

use App\Wiki;
use App\WikiDb;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Database\DatabaseManager;

/**
 * Prepends the MW Database, User with `deleted_` prefix and deletes WikiDB relation for a wiki
 */
class DeleteWikiDbJob extends Job implements ShouldBeUnique {
    private $wikiId;

    /**
     * @return void
     */
    public function __construct(int $wikiId) {
        $this->wikiId = $wikiId;
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId() {
        return strval($this->wikiId);
    }

    /**
     * @return void
     */
    public function handle(DatabaseManager $manager) {
        $wiki = Wiki::withTrashed()->where(['id' => $this->wikiId])->first();

        if (!$wiki) {
            $this->fail(new \RuntimeException("Wiki not found for {$this->wikiId}"));

            return;
        }

        if (!$wiki->deleted_at) {
            $this->fail(new \RuntimeException("Wiki {$this->wikiId} is not marked for deletion."));

            return;
        }

        $wikiDB = WikiDb::whereWikiId($this->wikiId)->first();

        if (!$wikiDB) {
            $this->fail(new \RuntimeException("WikiDb not found for {$this->wikiId}"));

            return;
        }

        try {

            $manager->purge('mw');
            $conn = $manager->connection('mw');

            if (!$conn instanceof \Illuminate\Database\Connection) {
                throw new \RuntimeException('Must be run on a PDO based DB connection');
            }

            $pdo = $conn->getPdo();
            $timestamp = Carbon::now()->timestamp;
            $deletedDatabaseName = "mwdb_deleted_{$timestamp}_{$this->wikiId}";

            if ($pdo->exec('USE ' . $wikiDB->name) === false) {
                throw new \RuntimeException('Failed to use database with dbname: ' . $wikiDB->name);
            }

            $tables = [];
            $result = $pdo->query('SHOW TABLES')->fetchAll();
            /*
            ^ array:2 [
            "Tables_in_<database_name>" => "<table_name>"
            0 => "<table_name>"
            ]
            */
            foreach ($result as $table) {
                $values = array_unique(array_values($table));
                if (count($values) !== 1) {
                    throw new \RuntimeException("Tried getting table names for wikiDB {$wikiDB->name} but failed");
                }

                $tables[] = $values[0];
            }

            if (empty($tables)) {
                throw new \RuntimeException("Tried getting table names for wikiDB {$wikiDB->name} but did not find any");
            }

            // Create the new database
            $pdo->exec(sprintf('CREATE DATABASE %s', $deletedDatabaseName));

            // iterate over each table and replace the prefix
            // and move it to the deleted database by using RENAME TABLE
            foreach ($tables as $table) {

                $replacedCount = 0;
                $tableWithoutPrefix = str_replace($wikiDB->prefix . '_', '', $table, $replacedCount);
                if ($replacedCount !== 1) {
                    throw new \RuntimeException("Did not find prefix '{$wikiDB->prefix}' in tablename '{$table}' ");
                }
                $pdo->exec(sprintf('RENAME TABLE %s.%s TO %s.%s', $wikiDB->name, $table, $deletedDatabaseName, $tableWithoutPrefix));
            }

            $pdo->exec(sprintf('DROP USER %s', $wikiDB->user));
        } catch (\Throwable $e) {
            $manager->purge('mw');
            $this->fail(new \RuntimeException('Failed to soft-soft delete ' . $wikiDB->name . ': ' . $e->getMessage()));

            return;
        }

        $wikiDB->delete();
        $manager->purge('mw');
    }
}
