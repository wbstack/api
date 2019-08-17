<?php

namespace App\Tests\Routes\Auth;

use App\User;
use App\Tests\TestCase;
use App\Tests\Routes\Traits\CrossSiteHeadersOnOptions;
use App\Tests\Routes\Traits\OptionsRequestAllowed;
use Laravel\Lumen\Testing\DatabaseTransactions;

class LoginTest extends TestCase {

    protected $route = 'auth/login';

    use CrossSiteHeadersOnOptions;
    use OptionsRequestAllowed;

    use DatabaseTransactions;

    public function testLoginFail_noExistingUser()
    {
        // This random user probably doesn't exist in the db
        $user = factory(User::class)->make();
        $this->post($this->route, [ 'email' => $user->email, 'password' => 'anyPassword' ])
        ->seeStatusCode(400);
    }

    public function testLoginFail_badPassword()
    {
        $user = factory(User::class)->create();
        $this->post($this->route, [ 'email' => $user->email, 'password' => 'someOtherPassword' ])
        ->seeStatusCode(400);
    }

    public function testLoginSuccess()
    {
        $password = 'apassword';
        $user = factory(User::class)->create(['password' => password_hash($password, PASSWORD_DEFAULT)]);
        $this->post($this->route, [ 'email' => $user->email, 'password' => $password ])
        ->seeStatusCode(200)
        ->seeJsonStructure(['email', 'isAdmin', 'token'])
        ->seeJsonContains(['email' => $user->email, 'isAdmin' => false]);
    }

}
