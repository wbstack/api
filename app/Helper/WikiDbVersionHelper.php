<?php

/* The purpose of this class to have one only place where
 * this map of mediawikiVersion -> dbVersion lives.
 */

namespace App\Helper;

/**
 * Exception thrown when a database version is not mapped in WikiDbVersionHelper.
 */
class UnknownDbVersionException extends \Exception {}

/**
 * Exception thrown when a mediawiki version is not mapped in WikiDbVersionHelper.
 */
class UnknownMwVersionException extends \Exception {}

class WikiDbVersionHelper {
    /** @var array<string, string> Map of DB version strings to MediaWiki version strings */
    private const DB_VERSION_TO_MW_VERSION = [
        'mw1.39-wbs1' => '139',
        'mw1.43-wbs1' => '143',
    ];

    static public function isValidDbVersion(string $dbVersionString): bool {
        return array_key_exists(
            $dbVersionString,
            self::DB_VERSION_TO_MW_VERSION
        );
    }

    static public function isValidMwVersion(string $mwVersionString): bool {
        return array_key_exists(
            $mwVersionString,
            array_flip(self::DB_VERSION_TO_MW_VERSION)
        );
    }

    /**
     * @throws UnknownMwVersionException
     */
    static public function getDbVersion(string $mwVersionString): string {
        if (self::isValidMwVersion($mwVersionString)) {
            return array_flip(self::DB_VERSION_TO_MW_VERSION)[$mwVersionString];
        }
        
        throw new UnknownMwVersionException("Unknown MediaWiki version string: '{$mwVersionString}'");
    }

    /**
     * @throws UnknownDbVersionException
     */
    static public function getMwVersion(string $dbVersionString): string {
        if (self::isValidDbVersion($dbVersionString)) {
            return self::DB_VERSION_TO_MW_VERSION[$dbVersionString];
        }
        
        throw new UnknownDbVersionException("Unknown database version string: '{$dbVersionString}'");
    }
}
