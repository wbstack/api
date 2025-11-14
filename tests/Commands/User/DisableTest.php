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

        $this->artisan('wbs-user:disable',
            [
                '--email' => self::EMAIL,
            ]
        )->assertExitCode(0);

        $newUser = User::firstWhere('id', $oldUser->id);

        $this->assertSame($oldUser->id, $newUser->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9A-Za-z]+@disabled-user.wikibase.cloud$/',
            $newUser->email
        );
        $this->assertFalse($newUser->hasVerifiedEmail());
        $this->assertNotSame($oldUser->password, $newUser->password);
    }

    public function testUserNotFound() {
        $this->artisan('wbs-user:disable',
            [
                '--email' => self::EMAIL,
            ]
        )->assertExitCode(2);
    }
}
