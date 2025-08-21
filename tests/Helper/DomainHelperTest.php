<?php

namespace Tests\Jobs;

use App\Helper\DomainHelper;
use Tests\TestCase;

class DomainHelperTest extends TestCase {
    /**
     * @return string[][]
     */
    public static function provideUnicodeDomains(): array {
        return [
            'Example IDNA encoding' => [
                'bücher.example',
                'xn--bcher-kva.example',
            ],
            'Example IDNA encoding #2 - Latin-1' => [
                'então.carolinadoran.com',
                'xn--ento-ioa.carolinadoran.com',
            ],
            'Example IDNA encoding #3 - Greek' => [
                'άλφα.wikibase.cloud',
                'xn--hxak3a7b.wikibase.cloud',
            ],
            'Example IDNA encoding #4 - Japanese' => [
                'ドメイン名例.wikibase.cloud',
                'xn--eckwd4c7cu47r2wf.wikibase.cloud',
            ],
            'Example IDNA encoding #5 - Emoji' => [
                '😃.wikibase.cloud',
                'xn--h28h.wikibase.cloud',
            ],
            'No double-encoding of "münchen.wikibase.cloud"' => [
                'xn--mnchen-3ya.wikibase.cloud',
                'xn--mnchen-3ya.wikibase.cloud',
            ],
            'No double-encoding of "então.carolinadoran.com"' => [
                'xn--ento-ioa.carolinadoran.com',
                'xn--ento-ioa.carolinadoran.com',
            ],
            'Output gets converted to lower case' => [
                'EXAMPLE',
                'example',
            ],
        ];
    }

    /**
     * @return string[][]
     */
    public static function provideAsciiDomains(): array {
        return [
            'Example IDNA decoding' => [
                'xn--bcher-kva.example',
                'bücher.example',
            ],
            'Example IDNA decoding #2 - Latin-1' => [
                'xn--ento-ioa.carolinadoran.com',
                'então.carolinadoran.com',
            ],
            'Example IDNA decoding #3 - Greek' => [
                'xn--hxak3a7b.wikibase.cloud',
                'άλφα.wikibase.cloud',
            ],
            'Example IDNA decoding #4 - Japanese' => [
                'xn--eckwd4c7cu47r2wf.wikibase.cloud',
                'ドメイン名例.wikibase.cloud',
            ],
            'Example IDNA decoding #5 - Emoji' => [
                'xn--h28h.wikibase.cloud',
                '😃.wikibase.cloud',
            ],
            'Domain in unicode stays the same' => [
                'münchen.wikibase.cloud',
                'münchen.wikibase.cloud',
            ],
            'Domain in unicode stays the same #2' => [
                'então.carolinadoran.com',
                'então.carolinadoran.com',
            ],
            'Output gets converted to lower case' => [
                'EXAMPLE',
                'example',
            ],
        ];
    }

    /**
     * @dataProvider provideUnicodeDomains
     */
    public function testEncoding($input, $expectedOutput) {
        $encoded = DomainHelper::encode($input);

        $this->assertSame(
            $encoded,
            $expectedOutput
        );
    }

    /**
     * @dataProvider provideAsciiDomains
     */
    public function testDecoding($input, $expectedOutput) {
        $decoded = DomainHelper::decode($input);

        $this->assertSame(
            $decoded,
            $expectedOutput
        );
    }
}
