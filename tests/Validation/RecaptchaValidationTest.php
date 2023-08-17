<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Rules\RecaptchaValidation;

class RecaptchaValidationTest extends TestCase
{
    public function test_example()
    {
        $this->assertTrue(app()->environment('testing'));

        $rule = new RecaptchaValidation;

        $this->assertFalse(
            $rule->passes('token', 'invalid')
        );
    }

    public function testSuccess()
    {
        $mockBuilder = $this->getMockBuilder(RecaptchaValidation::class)
            ->disableOriginalConstructor();

        $rule = $mockBuilder->getMock();



        // TODO! 
        // $this->assertTrue(
        //     $rule->passes('token', 'invalid')
        // );
    }
}
