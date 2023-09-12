<?php

namespace Tests\Routes\User;

use App\Invitation;
use App\Notifications\UserCreationNotification;
use App\User;
use App\Rules\ReCaptchaValidation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    protected $route = 'user/register';

    use OptionsRequestAllowed;
    use DatabaseTransactions;

    // TODO test password length when not deving

    private function mockReCaptchaValidation($passes=true)
    {
        // replace injected ReCaptchaValidation class with mock (ContactController::$recaptchaValidation)
        $mockRule = $this->createMock(ReCaptchaValidation::class);
        $mockRule->method('passes')
            ->willReturn($passes);

        $this->app->instance(ReCaptchaValidation::class, $mockRule);
    }

    public function testCreate_Success()
    {
        $this->mockReCaptchaValidation();
        Notification::fake();

        $invite = Invitation::factory()->create();
        $userToCreate = User::factory()->make();

        $resp = $this->json('POST', $this->route, [
          'email' => $userToCreate->email,
          'password' => 'anyPassword',
        ]);

        $resp->assertStatus(200)
        ->assertJsonStructure(['data' => ['email', 'id'], 'message', 'success'])
        ->assertJsonFragment(['email' => $userToCreate->email, 'success' => true]);

        Notification::assertSentTo(
            [User::whereEmail($userToCreate->email)->first()], UserCreationNotification::class
        );

        // SHIFT doesnt have missingFromDatabase
        //->missingFromDatabase('invitations', ['code' => $invite->code]);
    }

    public function testCreate_EmailAlreadyTaken()
    {
        $this->mockReCaptchaValidation();

        $invite = Invitation::factory()->create();
        $user = User::factory()->create();

        $this->json('POST', $this->route, [
          'email' => $user->email,
          'password' => 'anyPassword',
        ])
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => ['email']]);
    }

    public function testCreate_NoToken()
    {
        $this->mockReCaptchaValidation(false);

        $invite = Invitation::factory()->create();
        $user = User::factory()->make();

        $this->json('POST', $this->route, [
          'email' => $user->email,
          'password' => 'anyPassword',
        ])
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => ['recaptcha']]);
    }

    public function testCreate_NoEmailOrPassword()
    {
        $this->mockReCaptchaValidation();
        $this->json('POST', $this->route, [])
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => ['email', 'password']]);
    }

    public function testCreate_BadEmail()
    {
        $this->mockReCaptchaValidation();

        $invite = Invitation::factory()->create();

        $this->json('POST', $this->route, [
          'email' => 'notAnEmail',
          'password' => 'anyPassword',
        ])
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => ['email']]);
    }
}
