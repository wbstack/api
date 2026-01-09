<?php

namespace App\Services;

use Illuminate\Database\DatabaseManager;
use PDO;

class WikiUserEmailChecker {
    public function __construct(private DatabaseManager $db) {}

    public function findEmail(string $email): array {
        $this->db->purge('mw');
        $pdo = $this->db->connection('mw')->getPdo();

        $mwDatabases = $pdo
            ->query("SHOW DATABASES LIKE 'mwdb_%'")
            ->fetchAll(PDO::FETCH_COLUMN);

        $foundIn = [];

        foreach ($mwDatabases as $dbName) {
            $userTable = $this->findUserTable($pdo, $dbName);

            if (!$userTable) {
                continue;
            }

            if ($this->emailExists($pdo, $dbName, $userTable, $email)) {
                $foundIn[] = "{$dbName}.{$userTable}";
            }
        }

        return $foundIn;
    }

    private function findUserTable(PDO $pdo, string $dbName): ?string {
        $stmt = $pdo->prepare("
            SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = :db
              AND TABLE_NAME LIKE '%\_user'
            LIMIT 1
        ");

        $stmt->execute(['db' => $dbName]);

        return $stmt->fetchColumn() ?: null;
    }

    private function emailExists(PDO $pdo, string $dbName, string $table, string $email): bool {
        $stmt = $pdo->prepare("
            SELECT 1
            FROM {$dbName}.{$table}
            WHERE user_email = :email
            LIMIT 1
        ");

        $stmt->execute(['email' => $email]);

        return (bool) $stmt->fetch();
    }
}
