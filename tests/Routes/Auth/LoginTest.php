<?php

namespace Tests\Routes\Auth;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;

class LoginTest extends TestCase {
    protected $route = 'auth/login';

    use DatabaseTransactions;
    use OptionsRequestAllowed;

    protected function setUp(): void {
        parent::setUp();
        Artisan::call('passport:install', ['--no-interaction' => true]);
    }

    public function testLoginFailNoExistingUser() {
        // This random user probably doesn't exist in the db
        $user = User::factory()->make();
        $this->json('POST', $this->route, ['email' => $user->email, 'password' => 'anyPassword'])
            ->assertStatus(401);
    }

    public function testLoginFailBadPassword() {
        $user = User::factory()->create();
        $this->json('POST', $this->route, ['email' => $user->email, 'password' => 'someOtherPassword'])
            ->assertStatus(401);
    }

    public function testLoginSuccess() {
        $password = 'apassword';
        $user = User::factory()->create(['password' => password_hash($password, PASSWORD_DEFAULT)]);
        $response = $this->json('POST', $this->route, ['email' => $user->email, 'password' => $password]);
        $response->assertStatus(200);
        $response->assertJsonStructure(['user' => ['email']]);
        $response->assertCookie('laravel_token');
        $userResponsePart = $response->json('user');
        $this->assertEquals($user->email, $userResponsePart['email']);
    }

    public function testGet() {
        $user = User::factory()->create();
        $this->actingAs($user, 'api')
            ->get($this->route)
            ->assertStatus(200);
    }

    public function testDelete() {
        $user = User::factory()->create();
        $token = $user->createToken('logout-test');
        $otherToken = $user->createToken('other-session');

        $this->withCredentials()
            ->withUnencryptedCookie(Config::get('auth.cookies.key'), $token->accessToken)
            ->delete($this->route)
            ->assertStatus(204);

        $this->assertDatabaseHas('oauth_access_tokens', [
            'id' => $token->token->id,
            'revoked' => true,
        ]);
        $this->assertDatabaseHas('oauth_access_tokens', [
            'id' => $otherToken->token->id,
            'revoked' => false,
        ]);
    }
}
