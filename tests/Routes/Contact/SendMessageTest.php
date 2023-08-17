<?php

namespace Tests\Routes\Contact;

use App\Notifications\ContactNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

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

    public function testSendMessage_NoData()
    {
        putenv('PHPUNIT_RECAPTCHA_CHECK=0');

        $data = $this->postDataTemplateEmpty;

        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);
    }

    public function testSendMessage_InvalidDataSubject()
    {
        putenv('PHPUNIT_RECAPTCHA_CHECK=0');

        $data = $this->postDataTemplateValid;
        $data['message'] = "Hi!";
        $data['subject'] = "not-valid";
        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);
    }

    public function testSendMessage_MessageTooLong()
    {
        putenv('PHPUNIT_RECAPTCHA_CHECK=0');

        $data = $this->postDataTemplateValid;
        $data['message'] = str_repeat("Hi!", 10000);
        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);
    }

    public function testSendMessage_NameTooLong()
    {
        putenv('PHPUNIT_RECAPTCHA_CHECK=0');

        $data = $this->postDataTemplateValid;
        $data['name'] = str_repeat("Hi!", 10000);
        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);
    }

    public function testSendMessage_ContactDetailsTooLong()
    {
        putenv('PHPUNIT_RECAPTCHA_CHECK=0');

        $data = $this->postDataTemplateValid;
        $data['contactDetails'] = str_repeat("Hi!", 10000);
        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);
    }

    public function testSendMessage_Success()
    {
        putenv('PHPUNIT_RECAPTCHA_CHECK=0');

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
        Notification::fake();
        putenv('PHPUNIT_RECAPTCHA_CHECK=1');

        $response = $this->json('POST', $this->route, $this->postDataTemplateValid);
        $response->assertStatus(401);

        Notification::assertNothingSent();
    }
}
