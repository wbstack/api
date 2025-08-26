<?php

namespace Tests\Routes\User;

use App\Notifications\ResetPasswordNotification;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase {
    protected $route = 'user/forgotPassword';

    use DatabaseTransactions;
    use OptionsRequestAllowed;

    public function testForgotPasswordSubmissionSuccess() {
        Notification::fake();
        $user = User::factory()->create(['email' => 'foo+bar@example.com']);
        $this->json('POST', $this->route, ['email' => $user->email])
            ->assertStatus(200);
        Notification::assertSentTo(
            $user,
            function (ResetPasswordNotification $notification) use ($user) {
                return str_contains($notification->toMail($user)->data()['actionUrl'], 'foo%2Bbar%40example.com');
            }
        );
    }

    public function testForgotPasswordSubmissionNotFound() {
        Notification::fake();
        $user = User::factory()->create(['email' => 'foo+bar@example.com']);
        $this->json('POST', $this->route, ['email' => 'foo+baz@example.com'])
            ->assertStatus(200);
        Notification::assertNothingSent();
    }
}
