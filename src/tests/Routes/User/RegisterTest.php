<?php

namespace App\Tests\Routes\User;

use App\User;
use App\Invitation;
use App\Tests\TestCase;
use App\Tests\Routes\Traits\CrossSiteHeadersOnOptions;
use App\Tests\Routes\Traits\OptionsRequestAllowed;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;


class RegisterTest extends TestCase {

    protected $route = 'user/register';

    use CrossSiteHeadersOnOptions;
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
        Mail::shouldReceive('raw')->once();

        $invite = factory(Invitation::class)->create();
        $userToCreate = factory(User::class)->make();

        putenv('PHPUNIT_RECAPTCHA_CHECK=0');
        $resp = $this->post($this->route, [
          'email' => $userToCreate->email,
          'password' => 'anyPassword',
          'invite' => $invite->code,
        ]);
        putenv('PHPUNIT_RECAPTCHA_CHECK=1');

        $resp->seeStatusCode(200)
        ->seeJsonStructure(['data' => [ 'email', 'id' ],'message','success'])
        ->seeJsonContains(['email' => $userToCreate->email, 'success' => true])
        ->missingFromDatabase('invitations',['code' => $invite->code]);
    }

    public function testCreate_EmailAlreadyTaken()
    {
        $invite = factory(Invitation::class)->create();
        $user = factory(User::class)->create();
        $this->post($this->route, [
          'email' => $user->email,
          'password' => 'anyPassword',
          'invite' => $invite->code,
        ])
        ->seeStatusCode(422)
        ->seeJsonStructure(['email']);
    }

    public function testCreate_NoInvitation()
    {
      $this->markTestSkipped('Fixme');
        $user = factory(User::class)->make();
        $this->post($this->route, [
          'email' => $user->email,
          'password' => 'anyPassword',
        ])
        ->seeStatusCode(422)
        ->seeJsonStructure(['invite']);
    }

    public function testCreate_NoToken()
    {
      putenv('PHPUNIT_RECAPTCHA_CHECK=1');
      $invite = factory(Invitation::class)->create();
        $user = factory(User::class)->make();
        $this->post($this->route, [
          'email' => $user->email,
          'password' => 'anyPassword',
          'invite' => $invite->code,
        ])
        ->seeStatusCode(422)
        ->seeJsonStructure(['recaptcha']);
    }

    public function testCreate_NoEmailOrPassword()
    {
        $user = factory(User::class)->create();
        $this->post($this->route, [])
        ->seeStatusCode(422)
        ->seeJsonStructure(['email', 'password']);
    }

    public function testCreate_BadInvitation()
    {
        $user = factory(User::class)->create();
        $this->post($this->route, [
          'email' => $user->email,
          'password' => 'anyPassword',
          'bad' => 'someInvite',
        ])
        ->seeStatusCode(422)
        ->seeJsonStructure(['invite']);
    }

    public function testCreate_BadEmail()
    {
        $invite = factory(Invitation::class)->create();
        $this->post($this->route, [
          'email' => 'notAnEmail',
          'password' => 'anyPassword',
          'invite' => $invite->code,
        ])
        ->seeStatusCode(422)
        ->seeJsonStructure(['email']);
    }

}
