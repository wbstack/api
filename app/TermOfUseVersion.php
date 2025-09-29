<?php

namespace App;

enum TermOfUseVersion: string {
    case v0 = '2025-08-21';
    // case V1 = 'yyyy-mm-dd';
    // case V2 = 'yyyy-mm-dd';
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
