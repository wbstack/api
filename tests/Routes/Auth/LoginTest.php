<?php

namespace Tests\Routes\Auth;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;

class LoginTest extends TestCase
{
    protected $route = 'auth/login';

    use OptionsRequestAllowed;
    use RefreshDatabase;

    public function setUp (): void
    {
        parent::setUp();
        $this->artisan('passport:install', ['--no-interaction' => true]);
    }

    public function testLoginFail_noExistingUser()
    {
        // This random user probably doesn't exist in the db
        $user = User::factory()->make();
        $this->json('POST', $this->route, ['email' => $user->email, 'password' => 'anyPassword'])
        ->assertStatus(401);
    }

    public function testLoginFail_badPassword()
    {
        $user = User::factory()->create();
        $this->json('POST', $this->route, ['email' => $user->email, 'password' => 'someOtherPassword'])
        ->assertStatus(401);
    }

    public function testLoginSuccess()
    {
        $password = 'apassword';
        $user = User::factory()->create(['password' => password_hash($password, PASSWORD_DEFAULT)]);
        $response = $this->json('POST', $this->route, ['email' => $user->email, 'password' => $password]);
        $response->assertStatus(200);
        $response->assertJsonStructure(['user' => ['email'], 'token']);
        $userResponsePart = $response->json('user');
        $this->assertEquals($user->email, $userResponsePart['email']);
    }
}
