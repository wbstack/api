<?php

namespace App\Services;

use App\Wiki;
use Exception;

class MediaWikiHostResolver {
    // TODO: Move this mapping to a config file so that MW updates do not require code changes here.
    /** @var array<string, string> Map of DB version strings to MediaWiki backend version strings */
    private const DB_VERSION_TO_MW_VERSION = [
        'mw1.39-wbs1' => '139-app',
        'mw1.43-wbs1' => '143-app',
    ];

    // This service could have other methods in future, e.g. getBackendHostForWiki()
    // public function getBackendHostForWiki(Wiki $wiki): string {
    //     return $this->getBackendHostForDomain($wiki->domain);
    // }

    public function getBackendHostForDomain(string $domain): string {
        // TODO: should 'backend.default.svc.cluster.local' be an env var e.g. PLATFORM_MW_BACKEND_HOST_SUFFIX?
        return sprintf('mediawiki-%s-backend.default.svc.cluster.local', $this->getMwVersionForDomain($domain));
    }

    public function getMwVersionForDomain(string $domain): string {
        $dbVersion = Wiki::where('domain', $domain)
            ->first()
            ->wikiDb
            ->version;

        if (array_key_exists($dbVersion, self::DB_VERSION_TO_MW_VERSION)) {
            return self::DB_VERSION_TO_MW_VERSION[$dbVersion];
        }
        throw new UnknownDBVersionException("Unknown DB version '{$dbVersion}' for domain '{$domain}'.");
    }
}

/**
 * Exception thrown when a database version is not recognized in MediaWikiHostResolver.
 */
class UnknownDBVersionException extends Exception {}
