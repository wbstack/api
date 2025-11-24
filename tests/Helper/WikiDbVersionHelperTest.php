<?php

namespace Tests\Jobs;

use App\Helper\UnknownDbVersionException;
use App\Helper\WikiDbVersionHelper;
use Tests\TestCase;

class WikiDbVersionHelperTest extends TestCase {
    public function testUnknownDbVersion() {
        $this->expectException(UnknownDbVersionException::class);

        WikiDbVersionHelper::getMwVersion('invalidDbVersion');
    }

    public function testKnownMwVersion() {
        $this->assertSame(
            WikiDbVersionHelper::getMwVersion('mw1.39-wbs1'),
            '139'
        );
    }
}
