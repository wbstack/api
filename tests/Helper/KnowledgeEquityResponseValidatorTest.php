<?php

namespace Helper;

use App\Helper\KnowledgeEquityResponseValidator;
use Tests\TestCase;

class KnowledgeEquityResponseValidatorTest extends TestCase {
    private KnowledgeEquityResponseValidator $validator;

    protected function setUp(): void {
        parent::setUp();
        $this->validator = new KnowledgeEquityResponseValidator;
    }

    /**
     * @dataProvider validKnowledgeEquityResponsesProvider
     */
    public function testValidatePassesWithValidKnowledgeEquityResponse(array $knowledgeEquityResponse): void {
        $validator = $this->validator->validate($knowledgeEquityResponse);

        $this->assertTrue($validator->passes());
    }

    /**
     * @dataProvider invalidKnowledgeEquityResponsesProvider
     */
    public function testValidateFailsWithInvalidKnowledgeEquityResponse(array $knowledgeEquityResponse): void {
        $validator = $this->validator->validate($knowledgeEquityResponse);

        $this->assertTrue($validator->fails());
    }

    public static function validKnowledgeEquityResponsesProvider(): array {
        return [
            'yes' => [['selectedOption' => 'yes']],
            'no' => [['selectedOption' => 'no']],
            'unsure' => [['selectedOption' => 'unsure']],
            'unsaid' => [['selectedOption' => 'unsaid']],
            'null freeTextResponse' => [['selectedOption' => 'yes', 'freeTextResponse' => null]],
            'empty freeTextResponse' => [['selectedOption' => 'no', 'freeTextResponse' => '']],
            'short freeTextResponse' => [['selectedOption' => 'unsure', 'freeTextResponse' => 'I like to move it move it']],
            'freeTextResponse at max length' => [['selectedOption' => 'unsaid', 'freeTextResponse' => str_repeat('x', 3000)]],
        ];
    }

    public static function invalidKnowledgeEquityResponsesProvider(): array {
        return [
            'no selectedOption' => [[]],
            'invalid' => [['selectedOption' => 'invalid']],
            'empty' => [['selectedOption' => '']],
            'null' => [['selectedOption' => null]],
            'random string' => [['selectedOption' => 'random string']],
            'freeTextResponse exceeds max length' => [['selectedOption' => 'yes', 'freeTextResponse' => str_repeat('I like to move it move it ', 200)]],
        ];
    }
}
