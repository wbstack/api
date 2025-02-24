<?php

/* The purpose of this class is to help convert between Carbon objects
* used in this Platform API application and MWTimestamps which are used
* internally in some Mediawiki databases.
* See: https://www.mediawiki.org/wiki/Manual:Timestamp
*/

namespace App\Helper;
use Carbon\CarbonImmutable;

class MWTimestampHelper {

    private const MWTimestampFormat = 'YmdHis';

    static public function getCarbonFromMWTimestamp( string $MWTimestamp ): CarbonImmutable {
        $carbon = CarbonImmutable::createFromFormat( self::MWTimestampFormat, $MWTimestamp );
        if ($carbon === false) {
            throw new \Exception('Unable to create Carbon object');
        }
        return $carbon;
    }

    static public function getMWTimestampFromCarbon(CarbonImmutable $carbonImmutable): string {
        return $carbonImmutable->format(self::MWTimestampFormat);
    }
}