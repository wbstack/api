<?php

namespace Tests\Routes\Complaint;

use App\Notifications\ComplaintNotification;
use App\Rules\ReCaptchaValidation;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Feature\ReCaptchaValidationTest;

class SendMessageTest extends TestCase
{
    protected $route = 'complaint/sendMessage';

    protected $postDataTemplateEmpty = [
        'name'           => '',
        'mailAddress'    => '',
        'reason'         => '',
        'offendingUrls'  => '',
        'recaptcha'      => '',
    ];

    protected $postDataTemplateFilled = [
        'name'           => 'Jane Doe',
        'mailAddress'    => 'jane.doe@example.com',
        'reason'         => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.',
        'offendingUrls'  => 'https://example.com/1, https://example.com/2, https://example.com/3',
        'recaptcha'      => 'fake-token',
    ];

    private function mockReCaptchaValidation($passes=true)
    {
        // replace injected ReCaptchaValidation class with mock (ComplaintController::$recaptchaValidation)
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

    public function testSendMessage_InvalidMailAddress()
    {
        $this->mockReCaptchaValidation();

        $data = $this->postDataTemplateFilled;
        $data['mailAddress'] = "invalid-mail-address";
        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);
    }

    public function testSendMessage_ReasonTooLong()
    {
        $this->mockReCaptchaValidation();

        $data = $this->postDataTemplateFilled;
        $data['reason'] = str_repeat("Hi!", 10000);
        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);
    }

    public function testSendMessage_NameTooLong()
    {
        $this->mockReCaptchaValidation();

        $data = $this->postDataTemplateFilled;
        $data['name'] = str_repeat("Hi!", 10000);
        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);
    }

    public function testSendMessage_OffendingUrlsTooLong()
    {
        $this->mockReCaptchaValidation();

        $data = $this->postDataTemplateFilled;
        $data['offendingUrls'] = str_repeat("Hi!", 10000);
        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);
    }


    public function testSendMessage_NoNameNorMailAddress()
    {
        $this->mockReCaptchaValidation();
        Notification::fake();

        $data = $this->postDataTemplateFilled;
        $data['name'] = '';
        $data['mailAddress'] = '';

        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(200);
    }

    public function testSendMessage_Success()
    {
        $this->mockReCaptchaValidation();
        Notification::fake();

        $data = $this->postDataTemplateFilled;

        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(200);
        Notification::assertSentTo(new AnonymousNotifiable(), ComplaintNotification::class, function ($notification) {
            $this->assertSame(
                "dsa@wikibase.cloud",
                $notification->toMail(new AnonymousNotifiable())->from[0]
            );
            return true;
        });
    }

    public function testSendMessage_RecaptchaFailure()
    {
        $this->mockReCaptchaValidation(false);
        Notification::fake();

        $response = $this->json('POST', $this->route, $this->postDataTemplateFilled);
        $response->assertStatus(401);

        Notification::assertNothingSent();
    }
}
