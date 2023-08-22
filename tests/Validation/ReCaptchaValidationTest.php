<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Rules\ReCaptchaValidation;

class ReCaptchaValidationTest extends TestCase
{
    public function buildMockedReCaptchaRule($fakeResponse)
    {
        $mockRuleBuilder = $this->getMockBuilder(ReCaptchaValidation::class);
        $mockRuleBuilder->setConstructorArgs([
            config('recaptcha.secretKey', 'someSecret'),
            config('recaptcha.minScore', 0.5),
            config('app.url', 'localhost'),
        ]);
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

    public function buildReCaptchaFakeResponse(array $data=[])
    {
        $template = [
            'success'           => true,
            'hostname'          => 'localhost',
            'challenge_ts'      => date('Y-m-d\TH:i:s\Z', time() - 120),
            'apk_package_name'  => null,
            'score'             => config('recaptcha.min_score'),
            'action'            => 'default',
            'error-codes'       => [],
        ];

        return array_merge($template, $data);
    }

    public function testBypass()
    {
        $rule = new ReCaptchaValidation('secret', 0.0, 'localhost');

        $this->assertFalse(
            $rule->passes('token', 'bypass')
        );
    }

    public function testLowScore()
    {
        $fakeResponse = $this->buildReCaptchaFakeResponse([
            'score' => config('recaptcha.min_score') -1,
        ]);

        $mockRule = $this->buildMockedReCaptchaRule($fakeResponse, $this);

        $this->assertFalse(
            $mockRule->passes('token', '')
        );
    }

    public function testWrongHostname()
    {
        $fakeResponse = $this->buildReCaptchaFakeResponse();
        $fakeResponse['hostname'] = 'example.com';

        $mockRule = $this->buildMockedReCaptchaRule($fakeResponse, $this);

        $this->assertFalse(
            $mockRule->passes('token', '')
        );
    }

    public function testNoSuccess()
    {
        $fakeResponse = $this->buildReCaptchaFakeResponse();
        $fakeResponse['success'] = false;

        $mockRule = $this->buildMockedReCaptchaRule($fakeResponse, $this);

        $this->assertFalse(
            $mockRule->passes('token', '')
        );
    }

    public function testSuccess()
    {
        $fakeResponse = $this->buildReCaptchaFakeResponse();

        $mockRule = $this->buildMockedReCaptchaRule($fakeResponse, $this);

        $this->assertTrue(
            $mockRule->passes('token', '')
        );
    }
}
