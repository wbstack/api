<?php

namespace App\Services;

use App\Wiki;
use Exception;

/**
 * Exception thrown when a database version is not recognized in MediaWikiHostResolver.
 */
class UnknownDBVersionException extends Exception {}

/**
 * Exception thrown when a wiki is not found by domain in MediaWikiHostResolver.
 */
class UnknownWikiDomainException extends Exception {}

class MediaWikiHostResolver {
    // TODO: Move this mapping to a config file so that MW updates do not require code changes here.
    /** @var array<string, string> Map of DB version strings to MediaWiki version strings */
    private const DB_VERSION_TO_MW_VERSION = [
        'mw1.39-wbs1' => '139',
        'mw1.43-wbs1' => '143',
    ];

    // https://phabricator.wikimedia.org/T409530
    // This service could have other methods in future, e.g. getBackendHostForWiki()
    // public function getBackendHostForWiki(Wiki $wiki): string {
    //     return $this->getBackendHostForDomain($wiki->domain);
    // }

    public function getBackendHostForDomain(string $domain): string {
        // TODO: Move 'backend.default.svc.cluster.local' to an env variable (e.g. PLATFORM_MW_BACKEND_HOST_SUFFIX) for flexibility.
        return sprintf('mediawiki-%s-app-backend.default.svc.cluster.local', $this->getMwVersionForDomain($domain));
    }

    public function getWebHostForDomain(string $domain): string {
        // TODO: Move 'web.default.svc.cluster.local' to an env variable (e.g. PLATFORM_MW_WEB_HOST_SUFFIX) for flexibility.
        return sprintf('mediawiki-%s-app-web.default.svc.cluster.local', $this->getMwVersionForDomain($domain));
    }

    public function getApiHostForDomain(string $domain): string {
        // TODO: Move 'api.default.svc.cluster.local' to an env variable (e.g. PLATFORM_MW_API_HOST_SUFFIX) for flexibility.
        return sprintf('mediawiki-%s-app-api.default.svc.cluster.local', $this->getMwVersionForDomain($domain));
    }

    public function getMwVersionForDomain(string $domain): string {
        $wiki = Wiki::where('domain', $domain)->first();

        if (!$wiki) {
            throw new UnknownWikiDomainException("Unknown Wiki Domain '{$domain}'.");
        }

        $dbVersion = $wiki->wikiDb->version;

        if (array_key_exists($dbVersion, self::DB_VERSION_TO_MW_VERSION)) {
            return self::DB_VERSION_TO_MW_VERSION[$dbVersion];
        }
        throw new UnknownDBVersionException("Unknown DB version '{$dbVersion}' for domain '{$domain}'.");
    }
}
