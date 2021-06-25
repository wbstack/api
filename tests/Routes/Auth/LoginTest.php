<?php

namespace Tests\Routes\Auth;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;

class LoginTest extends TestCase
{
    protected $route = 'auth/login';

    use OptionsRequestAllowed;
    use DatabaseTransactions;

    public function testLoginFail_noExistingUser()
    {
        // This random user probably doesn't exist in the db
        $user = User::factory()->make();
        $this->json('POST', $this->route, ['email' => $user->email, 'password' => 'anyPassword'])
        ->assertStatus(401);
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
        $response = $this->json('POST', $this->route, ['email' => $user->email, 'password' => $password]);
        $response->assertStatus(200);
        $response->assertJsonStructure(['user' => ['email'], 'token']);
        $userResponsePart = $response->json('user');
        $this->assertEquals($user->email, $userResponsePart['email']);
    }
}
