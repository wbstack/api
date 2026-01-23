<?php

namespace Tests\Commands;

use App\Services\WikiUserEmailChecker;
use App\User;
use Mockery;
use Tests\TestCase;

class CheckUserEmailExistTest extends TestCase {
    protected function tearDown(): void {
        User::query()->delete();
    }

    public function testItFindsEmailInApiUsersTable() {
        User::factory()->create([
            'email' => 'user@example.com',
        ]);

        // Act & Assert
        $this->artisan('wbs-user:check-email', ['emails' => ['user@example.com']])
            ->expectsOutput('FOUND: user@example.com in apidb.users')
            ->assertExitCode(0);
    }

    public function testItReturnsNotFoundIfEmailDoesNotExist() {
        $this->artisan('wbs-user:check-email', ['emails' => ['nonexistent@example.com']])
            ->expectsOutput('NOT FOUND: nonexistent@example.com')
            ->assertExitCode(0);
    }

    public function testItChecksMultipleEmails() {
        User::factory()->create(['email' => 'user1@example.com']);

        $emails = ['user1@example.com', 'other@example.com'];

        $this->artisan('wbs-user:check-email', ['emails' => $emails])
            ->expectsOutput('FOUND: user1@example.com in apidb.users')
            ->expectsOutput('NOT FOUND: other@example.com')
            ->assertExitCode(0);
    }

    public function testEmailFoundInWikiDb() {
        $checker = Mockery::mock(WikiUserEmailChecker::class);

        $checker->shouldReceive('findEmail')
            ->with('test@example.com')
            ->andReturn(['mwdb_test.mwdb_test_user']);

        $this->app->instance(WikiUserEmailChecker::class, $checker);

        $this->artisan('wbs-user:check-email', [
            'emails' => ['test@example.com'],
        ])
            ->expectsOutput('FOUND: test@example.com in mwdb_test.mwdb_test_user')
            ->assertExitCode(0);
    }

    public function testCaseInsensitive() {
        User::factory()->create([
            'email' => 'Test@Example.com',
        ]);
        $exists = User::whereEmailInsensitive('tEsT@eXaMpLe.CoM')->exists();

        $this->assertTrue($exists);
    }
}
