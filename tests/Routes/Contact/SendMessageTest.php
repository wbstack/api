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
        $response->assertStatus(400);
    }

    public function testSendMessage_InvalidDataSubject()
    {
        $data = $this->postDataTemplateEmpty;
        $data['message'] = "Hi!";
        $data['subject'] = "not-valid";
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
        $recipient = config('app.contact-mail-recipient');
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
        $data = [
            'name'           => 'foo',
            'contactDetails' => 'bar',
            'subject'        => 'general-question',
            'message'        => 'baz',
            'recaptcha'      => 'fake-token',
        ];

        putenv('PHPUNIT_RECAPTCHA_CHECK=1');
        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(401);
    }
}
