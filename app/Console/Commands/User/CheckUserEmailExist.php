<?php

namespace App\Console\Commands\User;

use App\User;
use Illuminate\Console\Command;
use PDO;

class CheckUserEmailExist extends Command {
    protected $signature = 'wbs-user:check-email {emails*}';

    protected $description = 'Check if emails exist in apidb.users or any MediaWiki user table';

    public function handle(): int {
        $emails = $this->argument('emails');

        $manager = app()->db;
        $manager->purge('mw');
        $mwConn = $manager->connection('mw');
        $pdo = $mwConn->getPdo();

        $dbStmt = $pdo->query("SHOW DATABASES LIKE 'mwdb_%'");
        $mwDatabases = $dbStmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($emails as $email) {
            $found = false;

            // Check apidb.users
            if (User::whereEmail($email)->exists()) {
                $this->line("FOUND: {$email} in apidb.users");
                $found = true;
            }

            // Check MediaWiki databases
            foreach ($mwDatabases as $dbName) {
                // fetch user table name
                $tableStmt = $pdo->prepare("
                    SELECT TABLE_NAME
                    FROM INFORMATION_SCHEMA.TABLES
                    WHERE TABLE_SCHEMA = :db
                      AND TABLE_NAME LIKE '%\_user'
                    LIMIT 1
                ");
                $tableStmt->execute(['db' => $dbName]);
                $userTable = $tableStmt->fetchColumn();
                if (!$userTable) {
                    continue;
                }

                $query = "
                    SELECT user_id
                    FROM {$dbName}.{$userTable}
                    WHERE user_email = :email
                    LIMIT 1
                ";

                $emailStmt = $pdo->prepare($query);
                $emailStmt->execute(['email' => $email]);

                if ($emailStmt->fetch()) {
                    $this->line("FOUND: {$email} in {$dbName}.{$userTable}");
                    $found = true;
                }
            }

            if (!$found) {
                $this->line("NOT FOUND: {$email}");
            }

            $this->line('--------------------------------------------------');
        }

        return 0;
    }
}
