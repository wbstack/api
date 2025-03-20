<?php
namespace Tests\Jobs;

use Tests\TestCase;
use App\Helper\ProfileValidator;

class ProfileValidatorTest extends TestCase {
   
    /**
     * @dataProvider validProfileProvider
     */
    public function testProfileValidatorWorksWithValidProfile($profile): void {
        $validatorFactory = new ProfileValidator();
        $validator=$validatorFactory->validate($profile);
        $this->assertTrue($validator->passes());
    }

    /**
     * @dataProvider invalidProfileProvider
     */
    public function testProfileValidatorWorksWithInvalidProfile($profile): void {
        $validatorFactory = new ProfileValidator();
        $validator=$validatorFactory->validate($profile);
        $this->assertFalse($validator->passes());
    }

    private function validProfileProvider() {
        return [
            [ 'boring profile with no other' => [
                'purpose' => 'data_hub',
                'audience' => 'narrow',
                'temporality' => 'permanent',
            ] ],
            [ 'with other values' => [
                'purpose' => 'other',
                'purpose_other' => 'for fun',
                'audience' => 'other',
                'audience_other' => 'my cat',
                'temporality' => 'other',
                'temporality_other' => 'only in the past',
            ] ],
        ];
    }


    private function invalidProfileProvider() {
        return [
            [ 'missing other keys' => [
                'purpose' => 'other',
                'audience' => 'narrow',
                'temporality' => 'permanent',
            ] ],
            [ 'other keys when there should not be' => [
                'purpose' => 'data_hub',
                'purpose_other' => 'asdfasdf',
                'audience' => 'narrow',
                'temporality' => 'permanent',
            ] ],
        ];
    }
}