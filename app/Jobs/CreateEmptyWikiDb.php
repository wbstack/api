<?php

namespace App\Jobs;

use App\WikiDb;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\DatabaseManager;

/**
 * Only used in the migration from WBStack to wikibase.cloud
 * To be deleted after migration
 *
 * Example usage that will always provision a new DB:
 * php artisan job:dispatch CreateEmptyWikiDb
 */
class CreateEmptyWikiDb extends Job
{
    private $dbName;

    private $dbUser;

    private $dbPassword;

    private $prefix;

    private $wikiDb;

    public function __construct(string $prefix)
    {
        $this->onQueue(self::QUEUE_NAME_PROVISIONING);
        $this->dbName = 'mwdb_wbstack_'.substr(bin2hex(random_bytes(48)), 0, 10);
        $this->dbUser = 'mwu_'.substr(bin2hex(random_bytes(48)), 0, 10);
        $this->dbPassword = substr(bin2hex(random_bytes(48)), 0, 14);
        $this->prefix = $prefix;
    }

    public function getWikiDb() {
        return $this->wikiDb;
    }

    /**
     * @return void
     */
    public function handle( DatabaseManager $manager )
    {
        $manager->purge('mw');
        $conn = $manager->connection('mw');
        if (! $conn instanceof \Illuminate\Database\Connection) {
            $this->fail(new \RuntimeException('Must be run on a PDO based DB connection'));

            return; //safegaurd
        }
        $pdo = $conn->getPdo();

        // CREATE THE USER
        // This looks stupid because it is.
        // For some reason the mediawiki-db-manager user seems to be able to create user
        // but these exec call to the PDO seems to throw an exception saying:
        // PDOException: SQLSTATE[HY000]: General error: 1396 Operation CREATE USER failed for 'mwu_0985131dfa'@'%'
        // So, catch this exception and check the error state ourselves, and allow us to continue past this?
        try {
            $conn->statement("CREATE USER '".$this->dbUser."'@'%' IDENTIFIED BY '".$this->dbPassword."'");
        } catch (\Illuminate\Database\QueryException $e) {
            // Probably fine, and if not fine then the ALTER will fail below? :)
            $conn->statement("ALTER USER '".$this->dbUser."'@'%' IDENTIFIED BY '".$this->dbPassword."'");
        }

        // CREATE (maybe) AND USE DB
        if ($this->dbName) {
            if ($pdo->exec('CREATE DATABASE IF NOT EXISTS '.$this->dbName) === false) {
                $this->fail(
                    new \RuntimeException('Failed to create database with dbname: '.$this->dbName)
                );

                return; //safegaurd
            }
        } else {
            // Default to mediawiki
            $this->dbName = 'mediawiki';
        }
        if ($pdo->exec('USE '.$this->dbName) === false) {
            $this->fail(
                new \RuntimeException('Failed to use database with dbname: '.$this->dbName)
            );

            return; //safegaurd
        }

        // GRANT THE USER ACCESS TO THE DB
        // TODO more limited GRANTS...
        // TODO cant grant based on table prefix, so maybe do have seperate dbs...?
        if ($pdo->exec('GRANT ALL ON '.$this->dbName.'.* TO \''.$this->dbUser.'\'@\'%\'') === false) {
            $this->fail(
                new \RuntimeException('Failed to grant user: '.$this->dbUser)
            );

            return; //safegaurd
        }

        // Figure out the SQL version
        $stmt = $pdo->query("SELECT version() AS version");
        $fullVersion = $stmt->fetch()['version']; // "10.5.12-MariaDB-log"
        preg_match('/^(\d+\.\d+\.\d+)(?!\d).*?$/', $fullVersion, $versionMatches); // [ 0 => '10.5.12-MariaDB-log', 1 => '10.5.12' ]
        $sqlVersion = $versionMatches[1]; // '10.5.12'
        $aboveMariaDb1059 = version_compare($sqlVersion,'10.5.9'); // 1 = higher, 0 = same, -1 = lower
        $aboveMariaDb1052 = version_compare($sqlVersion,'10.5.2'); // 1 = higher, 0 = same, -1 = lower

        if($aboveMariaDb1052 >= 0 && $aboveMariaDb1059 == -1) {
            $this->fail(
                new \RuntimeException('Can not succeed on MariaDB versions between 10.5.2 and 10.5.9')
            );
        }
        if($aboveMariaDb1059 == -1) {
            // GRANT the user access to see slave status
            // GRANT REPLICATION CLIENT ON *.* TO 'mwu_36be7164b0'@'%'
            if ($pdo->exec('GRANT REPLICATION CLIENT ON *.* TO \''.$this->dbUser.'\'@\'%\'') === false) {
                $this->fail(
                    new \RuntimeException('Failed to grant user: '.$this->dbUser)
                );

                return;
            }
        }
        if($aboveMariaDb1059 >= 0) {
            // GRANT the user access to see slave status
            // Mariadb versions > 10.5.9 https://mariadb.com/kb/en/grant/#replica-monitor
            // https://mariadb.com/docs/reference/mdb/privileges/BINLOG_MONITOR/ required to query "SHOW MASTER STATUS"
            // GRANT REPLICA MONITOR, BINLOG MONITOR ON *.* TO 'mwu_36be7164b0'@'%'
            if ($pdo->exec('GRANT REPLICA MONITOR, BINLOG MONITOR ON *.* TO \''.$this->dbUser.'\'@\'%\'') === false) {
                $this->fail(
                    new \RuntimeException('Failed to grant REPLICA MONITOR to user: '.$this->dbUser)
                );

                return;
            }
        }

        // TODO per plan, create some record before the above transaction stuff happens..
        // so:
        //  - Add wikiDb record, stating pending state?
        //  - Create the DB and tables in a TRANSACTION
        //  - update the wikiDb Record on success
        //
        //  This way, if this wikiDb create fails, there is still a record of the DB that has been / is being created?
        $this->wikiDb = WikiDb::create([
          'name' => $this->dbName,
          'user' => $this->dbUser,
          'password' => $this->dbPassword,
          // TODO: this should be chaged in update.php
          'version' => "wbstack-migrate",
          'prefix' => $this->prefix,
      ]);

      $manager->purge('mw');
    }
}
