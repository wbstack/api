<?php

namespace Tests\Routes\Auth;

use App\User;
use Tests\TestCase;
use Tests\Routes\Traits\OptionsRequestAllowed;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LoginTest extends TestCase
{
    protected $route = 'auth/login';

    use OptionsRequestAllowed;
    use DatabaseTransactions;

    public function testLoginFail_noExistingUser()
    {
        // This random user probably doesn't exist in the db
        $user = factory(User::class)->make();
        $this->json('POST', $this->route, ['email' => $user->email, 'password' => 'anyPassword'])
        ->assertStatus(422);
    }

    public function testLoginFail_badPassword()
    {
        $user = factory(User::class)->create();
        $this->json('POST', $this->route, ['email' => $user->email, 'password' => 'someOtherPassword'])
        ->assertStatus(401);
    }

    public function testLoginSuccess()
    {
        $password = 'apassword';
        $user = factory(User::class)->create(['password' => password_hash($password, PASSWORD_DEFAULT)]);
        $this->json('POST', $this->route, ['email' => $user->email, 'password' => $password])
        ->assertStatus(200)
        ->assertJsonStructure(['email', 'isAdmin', 'token'])
        ->assertJsonFragment(['email' => $user->email, 'isAdmin' => false]);
    }
}
