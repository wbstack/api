<?php

namespace Tests\Jobs;

use App\Http\Middleware\ThrottleSignup;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ThrottleSignupTest extends TestCase {
    use RefreshDatabase;

    public $connectionsToTransact = ['mysql'];

    protected function setUp(): void {
        parent::setUp();
        // Tests running before this test do not clean up properly so this
        // also needs to happen in setUp
        User::query()->delete();
    }

    protected function tearDown(): void {
        User::query()->delete();
        parent::tearDown();
    }

    private function seedUsers(array $dates) {
        foreach ($dates as $date) {
            User::factory()->create(['created_at' => $date]);
        }
    }

    public function testOk() {
        $this->seedUsers(
            [
                Carbon::now()->subHours(2),
                Carbon::now()->subMinutes(23),
                Carbon::now()->subHours(4),
                Carbon::now()->subHours(2),
            ]
        );

        $request = new Request;
        $middleware = new ThrottleSignup;
        $called = false;

        $response = $middleware->handle($request, function ($req) use (&$called) {
            $called = true;

            return response('OK', 200);
        }, '3', 'PT1H');

        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'Expected 200 status code, got ' . $response->getStatusCode(),
        );

        $this->assertEquals(
            true,
            $called,
            "Expected callback to be called when it wasn't."
        );
    }

    public function testFailure() {
        $this->seedUsers(
            [
                Carbon::now()->subMinutes(2),
                Carbon::now()->subMinutes(23),
                Carbon::now()->subMinutes(4),
                Carbon::now()->subMinutes(2),
            ]
        );

        $request = new Request;
        $middleware = new ThrottleSignup;
        $called = false;

        $response = $middleware->handle($request, function ($req) use (&$called) {
            $called = true;

            return response('OK', 200);
        }, '3', 'PT1H');

        $this->assertEquals(
            503,
            $response->getStatusCode(),
            'Expected 503 status code, got ' . $response->getStatusCode(),
        );

        $this->assertEquals(
            false,
            $called,
            'Expected callback not to be called when it was.'
        );
    }

    public function testNoConfiguration() {
        $this->seedUsers(
            [
                Carbon::now()->subMinutes(2),
                Carbon::now()->subMinutes(23),
                Carbon::now()->subMinutes(4),
                Carbon::now()->subMinutes(2),
            ]
        );

        $request = new Request;
        $middleware = new ThrottleSignup;
        $called = false;

        $response = $middleware->handle($request, function ($req) use (&$called) {
            $called = true;

            return response('OK', 200);
        }, '', '');

        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'Expected 200 status code, got ' . $response->getStatusCode(),
        );

        $this->assertEquals(
            true,
            $called,
            'Expected callback to be called when it was not.'
        );
    }
}
