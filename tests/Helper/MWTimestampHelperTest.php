<?php

namespace Tests\Unit;

use App\Helper\MWTimestampHelper;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use PHPUnit\Framework\TestCase;

class MWTimestampHelperTest extends TestCase
{
    public function testGetCarbonFromMWTimestamp()
    {
        $mwTimestamp = '20240513123456';
        $expectedCarbon = CarbonImmutable::create(2024, 5, 13, 12, 34, 56);

        $carbon = MWTimestampHelper::getCarbonFromMWTimestamp($mwTimestamp);

        $this->assertEquals($expectedCarbon, $carbon);
    }

    public function testGetCarbonFromMWTimestampWithInvalidTimestamp()
    {
        $this->expectException(InvalidFormatException::class);

        $invalidMwTimestamp = 'invalid_timestamp';
        MWTimestampHelper::getCarbonFromMWTimestamp($invalidMwTimestamp);
    }

    public function testGetCarbonFromMWTimestampWithUnixTimestamp()
    {
        $this->expectException(InvalidFormatException::class);

        $UnixTimestamp = CarbonImmutable::now()->timestamp;
        MWTimestampHelper::getCarbonFromMWTimestamp($UnixTimestamp);
    }

    public function testGetMWTimestampFromCarbon()
    {
        $carbon = CarbonImmutable::create(2024, 5, 13, 12, 34, 56);
        $expectedMwTimestamp = '20240513123456';

        $mwTimestamp = MWTimestampHelper::getMWTimestampFromCarbon($carbon);

        $this->assertEquals($expectedMwTimestamp, $mwTimestamp);
    }
}