<?php

namespace Tests\Routes\User;

use App\User;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;

class ResetPasswordTest extends TestCase {
    protected $route = 'user/resetPassword';

    use DatabaseTransactions;
    use OptionsRequestAllowed;

    public function testForgotPasswordEmailSuccess() {
        $user = User::factory()->create();
        $passwordBroker = $this->app->make(PasswordBroker::class);
        $token = $passwordBroker->createToken($user);

        $this->json('POST', $this->route, [
            'email' => $user->email,
            'password' => 'AnyPassword122333',
            'password_confirmation' => 'AnyPassword122333',
            'token' => $token,
        ])->assertStatus(200);
    }
}
