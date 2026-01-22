<?php

namespace App\Services;

use Illuminate\Database\DatabaseManager;
use PDO;

class WikiUserEmailChecker {
    public function __construct(private DatabaseManager $db) {}

    public function findEmail(string $email): array {
        $this->db->purge('mw');
        $pdo = $this->db->connection('mw')->getPdo();

        $foundIn = [];

        $userTables = $this->loadAllUserTables($pdo);

        foreach ($userTables as $dbName => $userTable) {
            if ($this->emailExists($pdo, $dbName, $userTable, $email)) {
                $foundIn[] = "{$dbName}.{$userTable}";
            }
        }

        return $foundIn;
    }

    private function loadAllUserTables(PDO $pdo): array {
        $stmt = $pdo->query("
        SELECT TABLE_SCHEMA, TABLE_NAME
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA LIKE 'mwdb\_%'
        WHERE TABLE_SCHEMA LIKE 'mwdb_%'
          AND TABLE_NAME LIKE '%_user'
    ");

        $tablesByDb = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $tablesByDb[$row['TABLE_SCHEMA']] = $row['TABLE_NAME'];
        }

        return $tablesByDb;
    }

    private function emailExists(PDO $pdo, string $dbName, string $table, string $email): bool {
        $stmt = $pdo->prepare("
            SELECT 1
            FROM {$dbName}.{$table}
            WHERE LOWER(user_email) = LOWER(:email)
            LIMIT 1
        ");

        $stmt->execute(['email' => $email]);

        return (bool) $stmt->fetch();
    }
}
