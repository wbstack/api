<?php

namespace App\Jobs;

use App\WikiDb;
use Illuminate\Support\Facades\DB;

class ProvisionWikiDbJob extends Job
{
    private $prefix;

    // TODO should be injected somehow?
    private $newSqlFile = 'mw1.33-oc1';

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
            $dbName = 'mwdb_'.substr(bin2hex(random_bytes(24)), 0, 8);
        }
        if ($prefix === 'null' || $prefix === null) {
            $prefix = 'mwt_'.substr(bin2hex(random_bytes(24)), 0, 8);
        }

        $this->dbUser = substr(bin2hex(random_bytes(24)), 0, 12);
        $this->dbPassword = substr(bin2hex(random_bytes(24)), 0, 12);

        $this->prefix = $prefix;
        $this->dbName = $dbName;
    }

    /**
     * @return void
     */
    public function handle()
    {
        $pdo = DB::connection($this->dbConnection)->getPdo();

        // TODO if a custom dbname is used, check for conflicts first...
        // TODO check for conflicts with the prefix for tables too...

        if ($this->dbName) {
            if ($pdo->exec('CREATE DATABASE IF NOT EXISTS '.$this->dbName) === false) {
                throw new \RuntimeException(
            'Failed to create database with dbname: '.$this->dbName);
            }
            if ($pdo->exec('USE '.$this->dbName) === false) {
                throw new \RuntimeException(
            'Failed to use database with dbname: '.$this->dbName);
            }
        }

        // User stuff
        if ($pdo->exec('CREATE USER \''.$this->dbUser.'\'@\'%\' IDENTIFIED BY \''.$this->dbPassword.'\'') === false) {
            throw new \RuntimeException(
           'Failed to create user: '.$this->dbUser);
        }
        // TODO more limited GRANTS...
        // TODO cant grant based on table prefix, so maybe do have seperate dbs...?
        if ($pdo->exec('GRANT ALL ON '.$this->dbName.'.* TO \''.$this->dbUser.'\'@\'%\'') === false) {
            throw new \RuntimeException(
           'Failed to grant user: '.$this->dbUser);
        }

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

        $wikiDb = WikiDb::create([
          'name' => $this->dbName,
          'user' => $this->dbUser,
          'password' => $this->dbPassword,
          'version' => $this->newSqlFile,
          'prefix' => $this->prefix,
      ]);
    }
}
