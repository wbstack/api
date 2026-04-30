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

    private const ENDPOINT = '/api/test/authenticate-middleware';

    protected function setUp(): void {
        parent::setUp();

        Artisan::call('passport:keys', ['--force' => true]);
        Artisan::call('passport:client', [
            '--personal' => true,
            '--name' => 'Authenticate middleware test',
            '--no-interaction' => true,
        ]);

        Route::middleware('auth:api')->get(self::ENDPOINT, function (Request $request) {
            return response()->json([
                'email' => $request->user()->email,
            ]);
        });
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

    private function issueTokenFor(User $user): string {
        return $user->createToken('authenticate-middleware-test')->accessToken;
    }
}
