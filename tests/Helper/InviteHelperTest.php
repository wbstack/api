<?php

namespace Tests\Jobs;

use App\Helper\InviteHelper;
use Tests\TestCase;

class InviteHelperTest extends TestCase {
    public function testGeneratesACode() {
        $expectedPattern = "/wbcloud-(\d{4})-(\d{4})/";

        $sut = new InviteHelper(2, 4);
        $code = $sut->generate();
        $this->assertIsString($code);
        $this->assertMatchesRegularExpression($expectedPattern, $code);
    }

    public function testGeneratesAnotherCode() {
        $expectedPattern = "/wbcloud-(\d{12})-(\d{12})-(\d{12})/";

        $sut = new InviteHelper(3, 12);
        $code = $sut->generate();
        $this->assertIsString($code);
        $this->assertMatchesRegularExpression($expectedPattern, $code);
    }
}
