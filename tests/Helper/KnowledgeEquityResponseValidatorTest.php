<?php

namespace Helper;

use App\Helper\KnowledgeEquityResponseValidator;
use Tests\TestCase;

class KnowledgeEquityResponseValidatorTest extends TestCase {
    private KnowledgeEquityResponseValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new KnowledgeEquityResponseValidator();
    }

    /**
     *
     */
    public function testValidatePassesWithValidKnowledgeEquityResponse(): void
    {
        $knowledgeEquityResponse = [
            'selectedOption' => 'yes',
        ];

        $validator = $this->validator->validate($knowledgeEquityResponse);

        $this->assertTrue($validator->passes());
    }

    public function testValidateFailsWhenSelectedOptionIsMissing(): void
    {
        $knowledgeEquityResponse = [];

        $validator = $this->validator->validate($knowledgeEquityResponse);

        $this->assertTrue($validator->fails());
    }
    public function testValidateFailsWhenSelectedOptionIsInvalid(): void
    {
        $knowledgeEquityResponse = [
            'selectedOption' => 'invalid',
        ];

        $validator = $this->validator->validate($knowledgeEquityResponse);

        $this->assertTrue($validator->fails());
    }
}
