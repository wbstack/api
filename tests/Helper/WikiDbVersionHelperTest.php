<?php

namespace Tests\Jobs;

use App\Helper\WikiDbVersionHelper;
use App\Helper\UnknownMwVersionException;
use App\Helper\UnknownDbVersionException;
use Tests\TestCase;

class WikiDbVersionHelperTest extends TestCase {
    public function testUnknownDbVersion() {
        $this->expectException(UnknownDbVersionException::class);

        WikiDbVersionHelper::getMwVersion('invalidDbVersion');
    }

    public function testUnknownMwVersion() {
        $this->expectException(UnknownMwVersionException::class);

        WikiDbVersionHelper::getDbVersion('invalidMwVersion');
    }

    public function testKnownDbVersion() {
        $this->assertSame(
            WikiDbVersionHelper::getDbVersion('143'),
            'mw1.43-wbs1'
        );
    }

    public function testKnownMwVersion() {
        $this->assertSame(
            WikiDbVersionHelper::getMwVersion('mw1.39-wbs1'),
            '139'
        );
    }
}
