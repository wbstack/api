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
        'name'      => '',
        'email'     => '',
        'message'   => '',
        'url'       => '',
        'recaptcha' => '',
    ];

    protected $postDataTemplateFilled = [
        'name'      => 'Jane Doe',
        'email'     => 'jane.doe@example.com',
        'message'   => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.',
        'url'       => 'https://example.com/1, https://example.com/2, https://example.com/3',
        'recaptcha' => 'fake-token',
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
        $data['email'] = "invalid-mail-address";
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
        $data['email'] = "mail@example.com, foo@bar.com";
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
        $data['message'] = str_repeat("Hi!", 10000);
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
        $data['url'] = str_repeat("Hi!", 10000);
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
        $data['email'] = '';

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
