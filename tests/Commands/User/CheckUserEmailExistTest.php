<?php

namespace Tests\Commands;

use App\User;
use Tests\TestCase;

class CheckUserEmailExistTest extends TestCase {
    public function testItFindsEmailInApidbUsersTable() {
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
}
