<?php

namespace Tests\Routes\Contact;

use App\Notifications\ContactNotification;
use App\User;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;
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
        $data = $this->postDataTemplateEmpty;

        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(401);
    }

    public function testSendMessage_InvalidDataSubject()
    {
        $data = $this->postDataTemplateValid;
        $data['message'] = "Hi!";
        $data['subject'] = "not-valid";
        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);
    }

    public function testSendMessage_MessageTooLong()
    {
        $data = $this->postDataTemplateValid;
        $data['message'] = str_repeat("Hi!", 10000);
        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);
    }

    public function testSendMessage_NameTooLong()
    {
        $data = $this->postDataTemplateValid;
        $data['name'] = str_repeat("Hi!", 10000);
        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);
    }

    public function testSendMessage_ContactDetailsTooLong()
    {
        $data = $this->postDataTemplateValid;
        $data['contactDetails'] = str_repeat("Hi!", 10000);
        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);
    }

    public function testSendMessage_Success()
    {
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
