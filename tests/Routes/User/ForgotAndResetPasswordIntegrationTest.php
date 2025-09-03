<?php

namespace Tests\Routes\User;

use App\Notifications\ResetPasswordNotification;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ForgotAndResetPasswordIntegrationTest extends TestCase {
    protected $forgot_route = 'user/forgotPassword';

    protected $reset_route = 'user/resetPassword';

    use DatabaseTransactions;

    public function testForgotAndResetPassword() {
        Notification::fake();
        $user = User::factory()->create(['email' => 'foo+bar@example.com']);
        $this->json('POST', $this->forgot_route, ['email' => $user->email]);
        Notification::assertSentTo(
            $user,
            function (ResetPasswordNotification $notification) use ($user) {
                $this->json('POST', $this->reset_route, [
                    'email' => $user->email,
                    'password' => 'AnyPassword122333',
                    'password_confirmation' => 'AnyPassword122333',
                    'token' => $notification->token,
                ])->assertStatus(200);

                return true;
            }
        );
    }
}
