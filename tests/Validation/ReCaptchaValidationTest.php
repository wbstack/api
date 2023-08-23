<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Rules\ReCaptchaValidation;
use \ReCaptcha\ReCaptcha;

class ReCaptchaValidationTest extends TestCase
{
    public function buildMockedReCaptcha($fakeResponse)
    {
        $mockRuleBuilder = $this->getMockBuilder(ReCaptcha::class);
        $mockRuleBuilder->setConstructorArgs([
            config('recaptcha.secretKey', 'someSecret'),
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

    public function buildReCaptchaValidation($recaptcha=false, $minScore=false, $appUrl=false) {
        if (false === $recaptcha) {
            $recaptcha = new ReCaptcha('secret');
        }

        if (false === $minScore) {
            $minScore = config('recaptcha.min_score', 0.5);
        }

        if (false === $appUrl) {
            $appUrl = config('app.url', 'http://www.wbaas.localhost');
        }

        return new ReCaptchaValidation($recaptcha, $minScore, $appUrl);
    }

    public function testBypassFails()
    {
        $rule = $this->buildReCaptchaValidation();

        $this->assertFalse(
            $rule->passes('token', 'someToken')
        );
    }

    public function testLowScore()
    {
        $fakeResponse = $this->buildReCaptchaFakeResponse([
            'score' => config('recaptcha.min_score') -1,
        ]);

        $mockReCaptcha = $this->buildMockedReCaptcha($fakeResponse);
        $rule = $this->buildReCaptchaValidation($mockReCaptcha);

        $this->assertFalse(
            $rule->passes('token', 'someToken')
        );
    }

    public function testInactiveHostVerification()
    {
        $fakeResponse = $this->buildReCaptchaFakeResponse([
            'hostname' => 'example.com'
        ]);

        $mockReCaptcha = $this->buildMockedReCaptcha($fakeResponse);
        $rule = $this->buildReCaptchaValidation($mockReCaptcha, false, 'localhost');

        $this->assertTrue(
            $rule->passes('token', 'someToken')
        );
    }

    public function testWrongHostname()
    {
        $fakeResponse = $this->buildReCaptchaFakeResponse([
            'hostname' => 'example.com'
        ]);

        $mockReCaptcha = $this->buildMockedReCaptcha($fakeResponse);
        $rule = $this->buildReCaptchaValidation($mockReCaptcha);

        $this->assertFalse(
            $rule->passes('token', 'someToken')
        );
    }

    public function testNoSuccess()
    {
        $fakeResponse = $this->buildReCaptchaFakeResponse([
            'success' => false
        ]);

        $mockReCaptcha = $this->buildMockedReCaptcha($fakeResponse);
        $rule = $this->buildReCaptchaValidation($mockReCaptcha);

        $this->assertFalse(
            $rule->passes('token', 'someToken')
        );
    }

    public function testSuccess()
    {
        $fakeResponse = $this->buildReCaptchaFakeResponse();
        $mockReCaptcha = $this->buildMockedReCaptcha($fakeResponse);

        $rule = $this->buildReCaptchaValidation($mockReCaptcha);

        $this->assertTrue(
            $rule->passes('token', 'someToken')
        );
    }
}
