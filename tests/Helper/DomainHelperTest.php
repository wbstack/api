<?php

namespace Tests\Jobs;

use Tests\TestCase;
use App\Helper\DomainHelper;

class DomainHelperTest extends TestCase {
    /**
     * @return string[][]
     */
    public function provideUnicodeDomains(): array {
        return [
            'example IDNA encoding' => [
                'bücher.example',
                'xn--bcher-kva.example',
            ],
            'example IDNA encoding #2 - Latin-1' => [
                'então.carolinadoran.com',
                'xn--ento-ioa.carolinadoran.com'
            ],
            'example IDNA encoding #3 - Greek' => [
                'άλφα.wikibase.cloud',
                'xn--hxak3a7b.wikibase.cloud'
            ],
            'example IDNA encoding #4 - Japanese' => [
                'ドメイン名例.wikibase.cloud',
                'xn--eckwd4c7cu47r2wf.wikibase.cloud'
            ],
            'Prevent double-encoding of "münchen.wikibase.cloud"' => [
                'xn--mnchen-3ya.wikibase.cloud',
                'xn--mnchen-3ya.wikibase.cloud',
            ],
            'Prevent double-encoding of "então.carolinadoran.com"' => [
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
    public function provideAsciiDomains(): array {
        return [
            'example IDNA decoding' => [
                'xn--bcher-kva.example',
                'bücher.example',
            ],
            'example IDNA decoding #2 - Latin-1' => [
                'xn--ento-ioa.carolinadoran.com',
                'então.carolinadoran.com',
            ],
            'example IDNA decoding #3 - Greek' => [
                'xn--hxak3a7b.wikibase.cloud',
                'άλφα.wikibase.cloud',
            ],
            'example IDNA decoding #4 - Japanese' => [
                'xn--eckwd4c7cu47r2wf.wikibase.cloud',
                'ドメイン名例.wikibase.cloud',
            ],
            'Domain in unicode is stays the same' => [
                'münchen.wikibase.cloud',
                'münchen.wikibase.cloud',
            ],
            'Domain in unicode is stays the same #2' => [
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
