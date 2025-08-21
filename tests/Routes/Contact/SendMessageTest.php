<?php

namespace Tests\Routes\Contact;

use App\Notifications\ContactNotification;
use App\Rules\ReCaptchaValidation;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendMessageTest extends TestCase {
    protected $route = 'contact/sendMessage';

    protected $postDataTemplateEmpty = [
        'name' => '',
        'contactDetails' => '',
        'subject' => '',
        'message' => '',
        'recaptcha' => '',
    ];

    protected $postDataTemplateValid = [
        'name' => 'foo',
        'contactDetails' => 'bar',
        'subject' => 'general-question',
        'message' => 'baz',
        'recaptcha' => 'fake-token',
    ];

    protected $validSubjects = [
        'general-question',
        'feature-request',
        'report-a-problem',
        'give-feedback',
        'other',
    ];

    private function mockReCaptchaValidation($passes = true) {
        // replace injected ReCaptchaValidation class with mock (ContactController::$recaptchaValidation)
        $mockRule = $this->createMock(ReCaptchaValidation::class);
        $mockRule->method('passes')
            ->willReturn($passes);

        $this->app->instance(ReCaptchaValidation::class, $mockRule);
    }

    public function testSendMessageNoData() {
        $this->mockReCaptchaValidation(false);

        $data = $this->postDataTemplateEmpty;

        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(401);
    }

    public function testSendMessageInvalidDataSubject() {
        $this->mockReCaptchaValidation();

        $data = $this->postDataTemplateValid;
        $data['message'] = 'Hi!';
        $data['subject'] = 'not-valid';
        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);
    }

    public function testSendMessageMessageTooLong() {
        $this->mockReCaptchaValidation();

        $data = $this->postDataTemplateValid;
        $data['message'] = str_repeat('Hi!', 10000);
        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);
    }

    public function testSendMessageNameTooLong() {
        $this->mockReCaptchaValidation();

        $data = $this->postDataTemplateValid;
        $data['name'] = str_repeat('Hi!', 10000);
        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);
    }

    public function testSendMessageContactDetailsTooLong() {
        $this->mockReCaptchaValidation();

        $data = $this->postDataTemplateValid;
        $data['contactDetails'] = str_repeat('Hi!', 10000);
        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);
    }

    public function testSendMessageNoContactDetails() {
        $this->mockReCaptchaValidation();
        Notification::fake();

        $data = [
            'name' => 'foo',
            'subject' => 'general-question',
            'message' => 'baz',
            'recaptcha' => 'fake-token',
        ];

        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(200);
    }

    public function testSendMessageSuccess() {
        $this->mockReCaptchaValidation();
        Notification::fake();

        $data = [
            'name' => 'foo',
            'contactDetails' => 'bar',
            'subject' => 'general-question',
            'message' => 'baz',
            'recaptcha' => 'fake-token',
        ];

        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(200);
        Notification::assertSentTo(new AnonymousNotifiable, ContactNotification::class, function ($notification) {
            $this->assertSame(
                'contact-general-question@wikibase.cloud',
                $notification->toMail(new AnonymousNotifiable)->from[0]
            );

            return true;
        });
    }

    public function testSendMessageRecaptchaFailure() {
        $this->mockReCaptchaValidation(false);
        Notification::fake();

        $response = $this->json('POST', $this->route, $this->postDataTemplateValid);
        $response->assertStatus(401);

        Notification::assertNothingSent();
    }
}
