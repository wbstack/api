<?php

namespace Tests\Routes\Contact;

use Tests\TestCase;

class SendMessageTest extends TestCase
{
    protected $route = 'contact/sendMessage';

    protected $postDataTemplate = [
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
        $data = $this->postDataTemplate;

        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);
    }

    public function testSendMessage_InvalidDataSubject()
    {
        $data = $this->postDataTemplate;

        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(400);
    }

    public function testSendMessage_Success()
    {
        $data = $this->postDataTemplate;

        $response = $this->json('POST', $this->route, $data);
        $response->assertStatus(200);
    }
}
