<?php

namespace Tests;

use App\Services\WikiUserEmailChecker;
use App\WikiDb;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WikiUserEmailCheckerTest extends TestCase {
    use RefreshDatabase;

    private DatabaseManager $db;

    private $databases = [
        ['prefix' => 'db_1', 'name' => 'mwdb_1', 'emails' => ['user1@email.localhost', 'user2@email.localhost']],
        ['prefix' => 'db_2', 'name' => 'mwdb_2', 'emails' => ['user1@email.localhost']],
        ['prefix' => 'db_3', 'name' => 'mwdb_3', 'emails' => []],
        ['prefix' => 'db_4', 'name' => 'mwdb_4', 'emails' => ['UsEr4@EmAiL.lOcAlHoSt']],
    ];

    protected function setUp(): void {
        parent::setUp();
        $this->db = $this->app->make('db');
        $this->deleteDatabases();
        $pdo = $this->db->connection('mw')->getPdo();
        foreach ($this->databases as $database) {
            $pdo->exec("CREATE DATABASE {$database['name']}");
            $userTable = "{$database['name']}.{$database['prefix']}_user";
            $pdo->exec("CREATE TABLE {$userTable} (user_email TINYBLOB)");
            if ($database['emails']) {
                $users = implode(',', array_map(fn ($email) => "('$email')", $database['emails']));
                $pdo->exec("INSERT INTO {$userTable} VALUES {$users}");
            }
        }
    }

    protected function tearDown(): void {
        $this->deleteDatabases();
        WikiDb::query()->delete();
        parent::tearDown();
    }

    private function deleteDatabases(): void {
        $pdo = $this->db->connection('mw')->getPdo();
        foreach ($this->databases as $database) {
            $pdo->exec("DROP DATABASE IF EXISTS {$database['name']};");
        }
    }

    public function testCorrectDatabaseFound(): void {
        $checker = new WikiUserEmailChecker($this->db);
        $this->assertEquals(
            ['mwdb_1.db_1_user'],
            $checker->findEmail('user2@email.localhost')
        );
    }

    public function testEmailFoundInMultipleDatabases(): void {
        $checker = new WikiUserEmailChecker($this->db);
        $this->assertEqualsCanonicalizing(
            ['mwdb_1.db_1_user', 'mwdb_2.db_2_user'],
            $checker->findEmail('user1@email.localhost')
        );
    }

    public function testWikiUserEmailCheckerIsCaseInsensitive(): void {
        $checker = new WikiUserEmailChecker($this->db);
        $this->assertEquals(
            ['mwdb_4.db_4_user'],
            $checker->findEmail('uSer4@eMAil.localhost')
        );
    }
}
