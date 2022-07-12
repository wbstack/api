<?php

namespace Tests\Notification;

use App\Notifications\ResetPasswordNotification;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    protected $route1 = 'user/forgotPassword';
    protected $route2 = 'user/resetPassword';

    use OptionsRequestAllowed;
    use DatabaseTransactions;

    public function testForgotPasswordEmail_Success()
    {
        $this->withoutExceptionHandling();
        $user = User::factory()->create();
        $this->json('POST', $this->route1, ['email' => $user->email])
            ->assertStatus(200);
    }

    public function testForgotPassword_BadEmail()
    {
        $user = User::factory()->make();
        $this->json('POST', $this->route1, ['email' => $user->email])
            ->assertStatus(401);
    }

///////////////

    public function testResetPassword_Success()
    {
        $token = ResetPasswordNotification::factory()->create();
        $user = User::factory()->create();
        $this->json('POST', $this->route2, [
            'email' => $user->email,
            'password' => 'AnyPassword1234',
            'password_confirmation' => 'AnyPassword1234',
            'token' => $token
        ])
            ->assertStatus(200);
    }

//    public function testResetPassword_Failed()
//    {
//        $user = User::factory()->
//    }


}
