<?php

namespace Tests\Commands;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Notifications\EmailReverificationNotification;
use Illuminate\Support\Facades\Notification;

class ChangeEmailTest extends TestCase
{
    use DatabaseTransactions;

    const EMAIL_OLD = 'old@example.com';
    const EMAIL_NEW = 'new@example.com';
    
    private function createUser($email)
    {
        $user = new User([
            'email' => $email,
            'password' => 'worldsstrongestpassword'
        ]);
        $user->save();
        
        return $user;
    }

    public function testSuccess()
    {
        $this->markTestSkipped('Pollutes the deleted wiki list');
        Notification::fake();

        $oldUser = $this->createUser(self::EMAIL_OLD);

        $this->artisan('wbs-user:change-email',
            [
                '--from' => self::EMAIL_OLD,
                '--to' => self::EMAIL_NEW,
            ]
        )->assertExitCode(0);

        $newUser = User::firstWhere('email', self::EMAIL_NEW);

        $this->assertSame($oldUser->id, $newUser->id);
        $this->assertSame($newUser->email, self::EMAIL_NEW);
        $this->assertFalse($newUser->hasVerifiedEmail());

        Notification::assertSentTo([$newUser], EmailReverificationNotification::class);
    }

    public function testSame()
    {
        Notification::fake();

        $oldUser = $this->createUser(self::EMAIL_OLD);

        $this->artisan('wbs-user:change-email',
            [
                '--from' => self::EMAIL_OLD,
                '--to' => self::EMAIL_OLD,
            ]
        )->assertExitCode(2);

        $newUser = User::firstWhere('email', self::EMAIL_OLD);

        $this->assertSame($oldUser->id, $newUser->id);
        $this->assertSame($newUser->email, self::EMAIL_OLD);
        $this->assertFalse($newUser->hasVerifiedEmail());

        Notification::assertNothingSent();
    }

    public function testUserNotFound()
    {
        Notification::fake();

        $oldUser = $this->createUser(self::EMAIL_OLD);

        $this->artisan('wbs-user:change-email',
            [
                '--from' => self::EMAIL_NEW,
                '--to' => self::EMAIL_OLD,
            ]
        )->assertExitCode(1);

        $newUser = User::firstWhere('email', self::EMAIL_OLD);

        $this->assertSame($oldUser->id, $newUser->id);
        $this->assertSame($newUser->email, self::EMAIL_OLD);
        $this->assertFalse($newUser->hasVerifiedEmail());

        Notification::assertNothingSent();
    }
}
