<?php

namespace Tests\Routes\Contact;

use App\Notifications\ContactNotification;
use App\Rules\ReCaptchaValidation;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Feature\ReCaptchaValidationTest;

class SendMessageTest extends TestCase
{
    protected $route = 'contact/sendMessage';

    protected $postDataTemplateEmpty = [
        'name'           => '',
        'contactDetails' => '',
        'subject'        => '',
        'message'        => '',
        'recaptcha'      => '',
    ];

    protected $postDataTemplateValid = [
        'name'           => 'foo',
        'contactDetails' => 'bar',
        'subject'        => 'general-question',
        'message'        => 'baz',
        'recaptcha'      => 'fake-token',
    ];

    protected $validSubjects = [
        'general-question',
        'feature-request',
        'report-a-problem',
        'give-feedback',
        'other',
    ];

    public function mockReCaptchaValidation($passes=true)
    {
        // replace injected ReCaptchaValidation class with mock (ContactController::$recaptchaValidation)
        $mockRule = $this->createMock(ReCaptchaValidation::class);
        $mockRule->method('passes')
            ->willReturn($passes);
    
        $this->app->instance(ReCaptchaValidation::class, $mockRule);
    }

    public function testSendMessage_NoData()
    {
        $this->mockReCaptchaValidation(false);

        $data = $this->postDataTemplateEmpty;

        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(401);
    }

    public function testSendMessage_InvalidDataSubject()
    {
        $this->mockReCaptchaValidation();

        $data = $this->postDataTemplateValid;
        $data['message'] = "Hi!";
        $data['subject'] = "not-valid";
        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);
    }

    public function testSendMessage_MessageTooLong()
    {
        $this->mockReCaptchaValidation();

        $data = $this->postDataTemplateValid;
        $data['message'] = str_repeat("Hi!", 10000);
        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);
    }

    public function testSendMessage_NameTooLong()
    {
        $this->mockReCaptchaValidation();

        $data = $this->postDataTemplateValid;
        $data['name'] = str_repeat("Hi!", 10000);
        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);
    }

    public function testSendMessage_ContactDetailsTooLong()
    {
        $this->mockReCaptchaValidation();

        $data = $this->postDataTemplateValid;
        $data['contactDetails'] = str_repeat("Hi!", 10000);
        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);
    }


    public function testSendMessage_NoContactDetails()
    {
        $this->mockReCaptchaValidation();
        Notification::fake();

        $data = [
            'name'           => 'foo',
            'subject'        => 'general-question',
            'message'        => 'baz',
            'recaptcha'      => 'fake-token',
        ];

        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(200);
    }

    public function testSendMessage_Success()
    {
        $this->mockReCaptchaValidation();
        Notification::fake();

        $data = [
            'name'           => 'foo',
            'contactDetails' => 'bar',
            'subject'        => 'general-question',
            'message'        => 'baz',
            'recaptcha'      => 'fake-token',
        ];

        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(200);
        Notification::assertSentTo(new AnonymousNotifiable(), ContactNotification::class, function ($notification) {
            $this->assertSame(
                "contact-general-question@wikibase.cloud",
                $notification->toMail(new AnonymousNotifiable())->from[0]
            );
            return true;
        });
    }

    public function testSendMessage_RecaptchaFailure()
    {
        $this->mockReCaptchaValidation(false);
        Notification::fake();

        $response = $this->json('POST', $this->route, $this->postDataTemplateValid);
        $response->assertStatus(401);

        Notification::assertNothingSent();
    }
}
