<?php

/* The purpose of this class is to help convert between Carbon objects
* used in this Platform API application and MWTimestamps which are used
* internally in some Mediawiki databases.
* See: https://www.mediawiki.org/wiki/Manual:Timestamp
*/

namespace App\Helper;

use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;

class MWTimestampHelper {
    private const MWTimestampFormat = 'YmdHis';

    public static function getCarbonFromMWTimestamp(string $MWTimestamp): CarbonImmutable {
        $carbon = CarbonImmutable::createFromFormat(self::MWTimestampFormat, $MWTimestamp);

        if (!$carbon instanceof CarbonImmutable) {
            throw new InvalidFormatException('Unable to create Carbon object: parser did not return CarbonImmutable');
        }

        return $carbon;
    }

    public static function getMWTimestampFromCarbon(CarbonImmutable $carbonImmutable): string {
        return $carbonImmutable->format(self::MWTimestampFormat);
    }
}
