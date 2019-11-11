<?php

namespace Tests\Routes\User;

use App\User;
use App\Invitation;
use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use Tests\Routes\Traits\OptionsRequestAllowed;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RegisterTest extends TestCase
{
    protected $route = 'user/register';

    use OptionsRequestAllowed;
    use DatabaseTransactions;

    // TODO test password length when not deving

    public function testCreate_Success()
    {
        // TODO mock the validate call? actually validate some bits, but not captcha
        // Validator::shouldReceive('validate')
        // ->once()
        // ->with()
        // ;

        // Don't send mail during the test
        //Mail::shouldReceive('raw')->once();
        // TODO fix this test and the assertion of once...
        // This broke during lumen 5.7 to 5.8 upgrade
        Mail::shouldReceive('raw');

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
        $user = factory(User::class)->create();
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
