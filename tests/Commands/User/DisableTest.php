<?php

namespace Tests\Commands;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DisableTest extends TestCase {
    use DatabaseTransactions;

    const EMAIL = 'mail@example.com';

    private function createUser($email) {
        $user = new User([
            'email' => $email,
            'password' => 'worldsstrongestpassword',
        ]);
        $user->save();

        return $user;
    }

    public function testSuccess() {
        $oldUser = $this->createUser(self::EMAIL);
        $oldUserId = $oldUser->id;

        $this->artisan('wbs-user:disable',
            [
                '--email' => self::EMAIL
            ]
        )->assertExitCode(0);

        $newUser = User::firstWhere('id', $oldUserId);

        $this->assertSame($oldUser->id, $newUser->id);
        $this->assertSame($newUser->email, '');
        $this->assertFalse($newUser->hasVerifiedEmail());
    }

    public function testUserNotFound() {
        $this->artisan('wbs-user:disable',
            [
                '--email' => self::EMAIL
            ]
        )->assertExitCode(2);
    }
}
