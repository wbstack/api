<?php

namespace Tests\Middleware;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthenticateTest extends TestCase {
    use RefreshDatabase;

    private const string ENDPOINT = '/api/test/authenticate-middleware';

    protected function setUp(): void {
        parent::setUp();

        Artisan::call('passport:install', ['--no-interaction' => true]);

        // Register new test route with Authenticate middleware. This also tests the config in Kernel.php and auth.php.
        Route::middleware('auth:api')->get(self::ENDPOINT, fn(Request $request) => response()->json([
            'email' => $request->user()->email,
        ]));
    }

    public function testReturnsCustomJsonWhenUnauthenticated(): void {
        $this->json('GET', self::ENDPOINT)
            ->assertStatus(401)
            ->assertJson(['error' => 'Unauthenticated.']);
    }

    public function testAuthenticatesUsingPassportTokenFromCookie(): void {
        $user = User::factory()->create();

        $this->withCredentials()
            ->withUnencryptedCookie(Config::get('auth.cookies.key'), $this->issueTokenFor($user))
            ->json('GET', self::ENDPOINT)
            ->assertStatus(200)
            ->assertJson(['email' => $user->email]);
    }

    public function testFailsUsingInvalidPassportTokenFromCookie(): void {
        $this->withCredentials()
            ->withUnencryptedCookie(Config::get('auth.cookies.key'), 'this is an invalid token')
            ->json('GET', self::ENDPOINT)
            ->assertStatus(401)
            ->assertJson(['error' => 'Unauthenticated.']);
    }

    public function testAuthenticatesUsingPassportTokenFromAuthorizationHeader(): void {
        $user = User::factory()->create();

        $this->withCredentials()
            ->withHeader('Authorization', 'Bearer ' . $this->issueTokenFor($user))
            ->json('GET', self::ENDPOINT)
            ->assertStatus(200)
            ->assertJson(['email' => $user->email]);
    }

    public function testFailsUsingInvalidPassportTokenFromAuthorizationHeader(): void {
        $this->withCredentials()
            ->withHeader('Authorization', 'Bearer ' . 'this is an invalid token')
            ->json('GET', self::ENDPOINT)
            ->assertStatus(401)
            ->assertJson(['error' => 'Unauthenticated.']);
    }

    private function issueTokenFor(User $user): string {
        return $user->createToken('authenticate-middleware-test')->accessToken;
    }
}
