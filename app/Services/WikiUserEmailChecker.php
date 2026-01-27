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

        $userTables = $this->getAllMediaWikiUserTables($pdo);

        foreach ($userTables as $dbName => $userTable) {
            if ($this->emailExists($pdo, $dbName, $userTable, $email)) {
                $foundIn[] = "{$dbName}.{$userTable}";
            }
        }

        return $foundIn;
    }

    private function getAllMediaWikiUserTables(PDO $pdo): array {
        $stmt = $pdo->query("
            SELECT TABLE_SCHEMA, TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES
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

            -- converting from tinyblob data type, see https://github.com/wbstack/api/blob/main/database/mw/new/mw1.43-wbs2.sql#L1006
            WHERE LOWER(CONVERT(user_email USING utf8mb4)) = LOWER(:email)

            LIMIT 1
        ");

        $stmt->execute(['email' => $email]);

        return (bool) $stmt->fetch();
    }
}
