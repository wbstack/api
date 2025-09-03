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
        $validator = $validatorFactory->validate($profile);
        $this->assertTrue($validator->passes());
    }

    /**
     * @dataProvider invalidProfileProvider
     */
    public function testProfileValidatorWorksWithInvalidProfile($profile): void {
        $validatorFactory = new ProfileValidator;
        $validator = $validatorFactory->validate($profile);
        $this->assertFalse($validator->passes());
    }

    private function validProfileProvider() {
        return [
            ['boring profile with no other' => [
                'purpose' => 'data_hub',
                'audience' => 'narrow',
                'temporality' => 'permanent',
            ]],
            ['with other values' => [
                'purpose' => 'data_hub',
                'audience' => 'other',
                'audience_other' => 'my cat',
                'temporality' => 'other',
                'temporality_other' => 'only in the past',
            ]],
        ];
    }

    private function invalidProfileProvider() {
        return [
            ['missing other keys' => [
                'purpose' => 'data_hub',
                'audience' => 'narrow',
                'temporality' => 'other',
            ]],
            ['audience is empty string' => [
                'purpose' => 'data_hub',
                'audience' => '',
                'temporality' => 'other',
            ]],
            ['audience key present when purpose not data_hub' => [
                'purpose' => 'data_lab',
                'audience' => 'narrow',
                'temporality' => 'permanent',
            ]],
            ['other keys when there should not be' => [
                'purpose' => 'data_hub',
                'purpose_other' => 'asdfasdf',
                'audience' => 'narrow',
                'temporality' => 'permanent',
            ]],
        ];
    }
}
