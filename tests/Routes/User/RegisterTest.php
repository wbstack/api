<?php

namespace Tests\Routes\User;

use App\Notifications\UserCreationNotification;
use App\Rules\ReCaptchaValidation;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;

class RegisterTest extends TestCase {
    protected $route = 'user/register';

    use DatabaseTransactions;
    use OptionsRequestAllowed;

    // TODO test password length when not deving

    private function mockReCaptchaValidation($passes = true) {
        // replace injected ReCaptchaValidation class with mock (ContactController::$recaptchaValidation)
        $mockRule = $this->createMock(ReCaptchaValidation::class);
        $mockRule->method('passes')
            ->willReturn($passes);

        $this->app->instance(ReCaptchaValidation::class, $mockRule);
    }

    public function testCreateSuccess() {
        $this->mockReCaptchaValidation();
        Notification::fake();

        $userToCreate = User::factory()->make();

        $resp = $this->json('POST', $this->route, [
            'email' => $userToCreate->email,
            'password' => 'anyPassword',
            'recaptcha' => 'someToken',
        ]);

        $resp->assertStatus(200)
            ->assertJsonStructure(['data' => ['email', 'id'], 'message', 'success'])
            ->assertJsonFragment(['email' => $userToCreate->email, 'success' => true]);

        Notification::assertSentTo(
            [User::whereEmail($userToCreate->email)->first()], UserCreationNotification::class
        );
    }

    public function testCreateEmailAlreadyTaken() {
        $this->mockReCaptchaValidation();

        $user = User::factory()->create();

        $this->json('POST', $this->route, [
            'email' => $user->email,
            'password' => 'anyPassword',
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['email']]);
    }

    public function testCreateNoToken() {
        $this->mockReCaptchaValidation(false);

        $user = User::factory()->make();

        $this->json('POST', $this->route, [
            'email' => $user->email,
            'password' => 'anyPassword',
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['recaptcha']]);
    }

    public function testCreateNoEmailOrPassword() {
        $this->mockReCaptchaValidation();
        $this->json('POST', $this->route, [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['email', 'password']]);
    }

    public function testCreateBadEmail() {
        $this->mockReCaptchaValidation();

        $this->json('POST', $this->route, [
            'email' => 'notAnEmail',
            'password' => 'anyPassword',
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['email']]);
    }
}
