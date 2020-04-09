<?php

namespace App\Jobs;

use App\WikiDb;
use Illuminate\Support\Facades\DB;

class ProvisionWikiDbJob extends Job
{
    private $prefix;

    // TODO should be injected somehow?
    private $newSqlFile = 'mw1.33-wbs4';

    private $dbConnection = 'mw';

    /**
     * @var string|null|false
     * null results in the default database being used
     * false results in an auto generated name being used
     * string results in that string being used
     */
    private $dbName;

    private $dbUser;

    private $dbPassword;

    /**
     * @return void
     */
    public function __construct($prefix = null, $dbName = false)
    {
        if (preg_match('/[^A-Za-z0-9\-_]/', $prefix)) {
            throw new \InvalidArgumentException('Prefix must only contain [^A-Za-z0-9\-_], got '.$prefix);
        }

        if ($dbName !== null && preg_match('/[^A-Za-z0-9\-_]/', $dbName)) {
            throw new \InvalidArgumentException('dbName must only contain [^A-Za-z0-9\-_] or null, got '.$dbName);
        }

        // Auto generation and corrections
        // TODO this stuff could come from the model?
        if ($dbName === 'false' || $dbName === false) {
            $dbName = env('MW_DB_DATABASE');
        }
        if ($dbName === 'null' || $dbName === null) {
            $dbName = 'mwdb_'.substr(bin2hex(random_bytes(24)), 0, 10);
        }
        if ($prefix === 'null' || $prefix === null) {
            $prefix = 'mwt_'.substr(bin2hex(random_bytes(24)), 0, 10);
        }

        $this->dbUser = 'mwu_'.substr(bin2hex(random_bytes(24)), 0, 10);
        $this->dbPassword = substr(bin2hex(random_bytes(24)), 0, 14);

        $this->prefix = $prefix;
        $this->dbName = $dbName;
    }

    /**
     * @return void
     */
    public function handle()
    {
        $conn = DB::connection($this->dbConnection);
        $pdo = $conn->getPdo();

        // TODO if a custom dbname is used, check for conflicts first...
        // TODO check for conflicts with the prefix for tables too...

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
                throw new \RuntimeException(
                'Failed to create database with dbname: '.$this->dbName);
            }
        } else {
            // Default to mediawiki
            $this->dbName = 'mediawiki';
        }
        if ($pdo->exec('USE '.$this->dbName) === false) {
            throw new \RuntimeException(
            'Failed to use database with dbname: '.$this->dbName);
        }

        // GRANT THE USER ACCESS TO THE DB
        // TODO more limited GRANTS...
        // TODO cant grant based on table prefix, so maybe do have seperate dbs...?
        if ($pdo->exec('GRANT ALL ON '.$this->dbName.'.* TO \''.$this->dbUser.'\'@\'%\'') === false) {
            throw new \RuntimeException(
               'Failed to grant user: '.$this->dbUser);
        }
        // GRANT the user access to see slave status
        // GRANT REPLICATION CLIENT ON *.* TO 'mwu_36be7164b0'@'%'
        if ($pdo->exec('GRANT REPLICATION CLIENT ON *.* TO \''.$this->dbUser.'\'@\'%\'') === false) {
            throw new \RuntimeException(
               'Failed to grant user: '.$this->dbUser);
        }

        // ADD THE TABLES
        // Get SQL statements to run
        $rawSql = file_get_contents(__DIR__.'/../../database/mw/new/'.$this->newSqlFile.'.sql');
        $prefixedSql = str_replace('<<prefix>>', $this->prefix, $rawSql);
        $sqlParts = explode("\n\n", $prefixedSql);

        foreach ($sqlParts as $part) {
            if (strpos($part, '--') === 0) {
                // Skip comment blocks
                continue;
            }

            // Execute each chunk of SQL...
            if ($pdo->exec($part) === false) {
                throw new \RuntimeException(
                'SQL execution failed for prefix '.$prefix.' SQL part: '.$part);
            }
        }

        // TODO per plan, create some record before the above transaction stuff happens..
        // so:
        //  - Add wikiDb record, stating pending state?
        //  - Create the DB and tables in a TRANSACTION
        //  - update the wikiDb Record on success
        //
        //  This way, if this wikiDb create fails, there is still a record of the DB that has been / is being created?
        $wikiDb = WikiDb::create([
          'name' => $this->dbName,
          'user' => $this->dbUser,
          'password' => $this->dbPassword,
          'version' => $this->newSqlFile,
          'prefix' => $this->prefix,
      ]);
    }
}
