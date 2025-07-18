<?php

namespace Tests\Routes\Complaint;

use App\ComplaintRecord;
use App\Notifications\ComplaintNotification;
use App\Rules\ReCaptchaValidation;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Feature\ReCaptchaValidationTest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SendMessageTest extends TestCase
{
    use RefreshDatabase;
    
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
    private function assertRecordCount(int $count)
    {
        $this->assertEquals(ComplaintRecord::count(), $count);
    }

    private function assertComplaintMarkedAsDispatched()
    {
        $complaintRecord = ComplaintRecord::first();
        $this->assertNotEmpty($complaintRecord->dispatched_at);
    }

    private function assertComplaintNotMarkedAsDispatched()
    {
        $complaintRecord = ComplaintRecord::first();
        $this->assertEmpty($complaintRecord->dispatched_at);
    }

    private function assertComplaintRecorded()
    {
        $this->assertRecordCount(1);
    }

    private function assertComplaintNotRecorded()
    {
        $this->assertRecordCount(0);
    }

    public function testRecordOnMailFail()
    {
        $this->mockReCaptchaValidation();

        $data = $this->postDataTemplateFilled;

        try {
            $response = $this->json('POST', $this->route, $data);
        } catch(\Symfony\Component\Mailer\Exception\TransportException $e) {
            return;
        }

        $this->assertNotEquals($response->status(), 200);

        $this->assertComplaintRecorded();
        $this->assertComplaintNotMarkedAsDispatched();
    }

    public function testSendMessage_NoData()
    {
        Notification::fake();
        $this->mockReCaptchaValidation(false);

        $data = $this->postDataTemplateEmpty;

        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(401);

        Notification::assertNothingSent();
        $this->assertComplaintNotRecorded();
    }

    public function testSendMessage_InvalidMailAddressRfc()
    {
        Notification::fake();
        $this->mockReCaptchaValidation();

        $data = $this->postDataTemplateFilled;
        $data['mailAddress'] = "invalid-mail-address";
        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);

        $this->assertEquals(ComplaintRecord::count(), 0);

        Notification::assertNothingSent();
        $this->assertComplaintNotRecorded();
    }

    public function testSendMessage_InvalidMailAddressMulti()
    {
        Notification::fake();
        $this->mockReCaptchaValidation();

        $data = $this->postDataTemplateFilled;
        $data['mailAddress'] = "mail@example.com, foo@bar.com";
        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);

        Notification::assertNothingSent();
        $this->assertComplaintNotRecorded();
    }

    public function testSendMessage_ReasonTooLong()
    {
        Notification::fake();
        $this->mockReCaptchaValidation();

        $data = $this->postDataTemplateFilled;
        $data['reason'] = str_repeat("Hi!", 10000);
        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);

        Notification::assertNothingSent();
        $this->assertComplaintNotRecorded();
    }

    public function testSendMessage_NameTooLong()
    {
        Notification::fake();
        $this->mockReCaptchaValidation();

        $data = $this->postDataTemplateFilled;
        $data['name'] = str_repeat("Hi!", 10000);
        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);

        Notification::assertNothingSent();
        $this->assertComplaintNotRecorded();
    }

    public function testSendMessage_OffendingUrlsTooLong()
    {
        Notification::fake();
        $this->mockReCaptchaValidation();

        $data = $this->postDataTemplateFilled;
        $data['offendingUrls'] = str_repeat("Hi!", 10000);
        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);

        Notification::assertNothingSent();
        $this->assertComplaintNotRecorded();
    }

    public function testSendMessage_NoNameNorMailAddress()
    {
        Notification::fake();
        $this->mockReCaptchaValidation();

        $data = $this->postDataTemplateFilled;
        $data['name'] = '';
        $data['mailAddress'] = '';

        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(200);

        Notification::assertCount(1);
        $this->assertComplaintRecorded();
        $this->assertComplaintMarkedAsDispatched();
    }

    public function testSendMessage_Success()
    {
        Notification::fake();
        $this->mockReCaptchaValidation();

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

        Notification::assertCount(2);
        $this->assertComplaintRecorded();
        $this->assertComplaintMarkedAsDispatched();
    }

    public function testSendMessage_RecaptchaFailure()
    {
        Notification::fake();
        $this->mockReCaptchaValidation(false);

        $response = $this->json('POST', $this->route, $this->postDataTemplateFilled);
        $response->assertStatus(401);

        Notification::assertNothingSent();
        $this->assertComplaintNotRecorded();
    }
}
