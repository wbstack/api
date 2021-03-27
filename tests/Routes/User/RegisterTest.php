<?php

namespace Tests\Routes\User;

use App\Invitation;
use App\Notifications\UserCreationNotification;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    protected $route = 'user/register';

    use OptionsRequestAllowed;
    use DatabaseTransactions;

    // TODO test password length when not deving

    public function testCreate_Success()
    {
        Notification::fake();

        $invite = factory(Invitation::class)->create();
        $userToCreate = factory(User::class)->make();

        putenv('PHPUNIT_RECAPTCHA_CHECK=0');
        $resp = $this->json('POST', $this->route, [
          'email' => $userToCreate->email,
          'password' => 'anyPassword',
          'invite' => $invite->code,
        ]);
        putenv('PHPUNIT_RECAPTCHA_CHECK=1');

        $resp->assertStatus(200)
        ->assertJsonStructure(['data' => ['email', 'id'], 'message', 'success'])
        ->assertJsonFragment(['email' => $userToCreate->email, 'success' => true]);

        Notification::assertSentTo(
            [User::whereEmail($userToCreate->email)->first()], UserCreationNotification::class
        );

        // SHIFT doesnt have missingFromDatabase
        //->missingFromDatabase('invitations', ['code' => $invite->code]);
    }

    public function testCreate_EmailAlreadyTaken()
    {
        $invite = factory(Invitation::class)->create();
        $user = factory(User::class)->create();
        $this->json('POST', $this->route, [
          'email' => $user->email,
          'password' => 'anyPassword',
          'invite' => $invite->code,
        ])
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => ['email']]);
    }

    public function testCreate_NoInvitation()
    {
        $this->markTestSkipped('Fixme');
        $user = factory(User::class)->make();
        $this->json('POST', $this->route, [
          'email' => $user->email,
          'password' => 'anyPassword',
        ])
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => ['invite']]);
    }

    public function testCreate_NoToken()
    {
        putenv('PHPUNIT_RECAPTCHA_CHECK=1');
        $invite = factory(Invitation::class)->create();
        $user = factory(User::class)->make();
        $this->json('POST', $this->route, [
          'email' => $user->email,
          'password' => 'anyPassword',
          'invite' => $invite->code,
        ])
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => ['recaptcha']]);
    }

    public function testCreate_NoEmailOrPassword()
    {
        $this->json('POST', $this->route, [])
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => ['email', 'password']]);
    }

    public function testCreate_BadInvitation()
    {
        $user = factory(User::class)->create();
        $this->json('POST', $this->route, [
          'email' => $user->email,
          'password' => 'anyPassword',
          'bad' => 'someInvite',
        ])
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => ['invite']]);
    }

    public function testCreate_BadEmail()
    {
        $invite = factory(Invitation::class)->create();
        $this->json('POST', $this->route, [
          'email' => 'notAnEmail',
          'password' => 'anyPassword',
          'invite' => $invite->code,
        ])
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => ['email']]);
    }
}
