<?php

namespace Tests\Jobs;

use App\Helper\ProfileValidator;
use Tests\TestCase;

class ProfileValidatorTest extends TestCase {
    /**
     * @dataProvider validProfileProvider
     */
    public function testProfileValidatorWorksWithValidProfile($profile): void {
        $validatorFactory = new ProfileValidator;
        $validator = $validatorFactory->getValidator($profile);
        $this->assertTrue($validator->passes());
    }

    /**
     * @dataProvider invalidProfileProvider
     */
    public function testProfileValidatorWorksWithInvalidProfile($profile): void {
        $validatorFactory = new ProfileValidator;
        $validator = $validatorFactory->getValidator($profile);
        $this->assertFalse($validator->passes());
    }

    public static function validProfileProvider(): iterable {
        yield 'boring profile with no other' => [[
            'purpose' => 'data_hub',
            'audience' => 'narrow',
            'temporality' => 'permanent',
        ]];

        yield 'with other values' => [[
            'purpose' => 'data_hub',
            'audience' => 'other',
            'audience_other' => 'my cat',
            'temporality' => 'other',
            'temporality_other' => 'only in the past',
        ]];
    }

    public static function invalidProfileProvider(): iterable {
        yield 'missing other keys' => [[
            'purpose' => 'data_hub',
            'audience' => 'narrow',
            'temporality' => 'other',
        ]];

        yield 'audience is empty string' => [[
            'purpose' => 'data_hub',
            'audience' => '',
            'temporality' => 'other',
        ]];

        yield 'audience key present when purpose not data_hub' => [[
            'purpose' => 'data_lab',
            'audience' => 'narrow',
            'temporality' => 'permanent',
        ]];

        yield 'other keys when there should not be' => [[
            'purpose' => 'data_hub',
            'purpose_other' => 'asdfasdf',
            'audience' => 'narrow',
            'temporality' => 'permanent',
        ]];
    }
}
