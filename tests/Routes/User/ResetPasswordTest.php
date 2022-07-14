<?php

namespace Tests\Routes\User;

use App\Notifications\ResetPasswordNotification;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    protected $route = 'user/forgotPassword';

    use OptionsRequestAllowed;
    use DatabaseTransactions;

    public function testCreate_Success()
    {
        Notification::fake();
        $user = User::factory()->create();

        $resp = $this->json('POST', $this->route, [
            'email' => $user->email,
        ]);

        $resp->assertStatus(200);
//            ->assertJsonStructure(['data' => ['email', 'id'], 'message', 'success'])
//            ->assertJsonFragment(['email' => $user->email, 'Success' => true]);

        Notification::assertSentTo(
            [User::whereEmail($user->email)->first()], ResetPasswordNotification::class
        );
    }
}
