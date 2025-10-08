<?php

namespace App;

enum TermsOfUseVersion: string {
    // case v2 = 'yyyy-mm-dd';
    // case v1 = 'yyyy-mm-dd';
    case v0 = '2025-08-21';

    public static function latest(): self {
        $latestVersion = self::v0;
        $latestNum = 0;
        foreach (self::cases() as $case) {
            if (!str_starts_with($case->name, 'v')) {
                continue;
            }
            $n = (int) substr($case->name, 1);
            if ($n > $latestNum) {
                $latestNum = $n;
                $latestVersion = $case;
            }
        }

        return $latestVersion;
    }
}
