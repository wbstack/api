<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Rules\RecaptchaValidation;

class RecaptchaValidationTest extends TestCase
{
    protected function getMockedRule($fakeResponse)
    {
        $mockRuleBuilder = $this->getMockBuilder(RecaptchaValidation::class);
        $mockRuleBuilder->onlyMethods([
            'verify'
        ]);

        $mockRule = $mockRuleBuilder->getMock();
        $mockRule->method('verify')
        ->willReturn(
            \ReCaptcha\Response::fromJson(
                json_encode($fakeResponse)
            )
        );

        return $mockRule;
    }

    protected function getValidFakeResponse()
    {
        return [
            'hostname' => RecaptchaValidation::getHostname(),
            'score'    => config('recaptcha.min_score'),
            'success'  => true,
        ];
    }

    public function testBypass()
    {
        $rule = new RecaptchaValidation;

        $this->assertFalse(
            $rule->passes('token', 'bypass')
        );
    }

    public function testLowScore()
    {
        $fakeResponse = $this->getValidFakeResponse();
        $fakeResponse['score'] = config('recaptcha.min_score') - 1;

        $mockRule = $this->getMockedRule($fakeResponse);

        $this->assertFalse(
            $mockRule->passes('token', '')
        );
    }

    public function testWrongHostname()
    {
        $fakeResponse = $this->getValidFakeResponse();
        $fakeResponse['hostname'] = 'example.com';

        $mockRule = $this->getMockedRule($fakeResponse);

        $this->assertFalse(
            $mockRule->passes('token', '')
        );
    }

    public function testNoSuccess()
    {
        $fakeResponse = $this->getValidFakeResponse();
        $fakeResponse['success'] = false;

        $mockRule = $this->getMockedRule($fakeResponse);

        $this->assertFalse(
            $mockRule->passes('token', '')
        );
    }

    public function testSuccess()
    {
        $fakeResponse = $this->getValidFakeResponse();

        $mockRule = $this->getMockedRule($fakeResponse);

        $this->assertTrue(
            $mockRule->passes('token', '')
        );
    }
}
