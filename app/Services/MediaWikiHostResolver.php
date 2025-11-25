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
    // keep in sync with App\Http\Controllers\Backend\WikiDbVersionController
    /** @var array<string, string> Map of DB version strings to MediaWiki version strings */
    private const DB_VERSION_TO_MW_VERSION = [
        'mw1.39-wbs1' => '139',
        'mw1.43-wbs1' => '143',
    ];

    /**
     * @throws UnknownDBVersionException
     * @throws UnknownWikiDomainException
     */
    public function getHostsForDomain(string $domain): array {
        $mwVersionForDomain = $this->getMwVersionForDomain($domain);

        // TODO: Make hosts format configurable for flexibility
        return [
            'web' => sprintf('mediawiki-%s-app-web.default.svc.cluster.local', $mwVersionForDomain),
            'backend' => sprintf('mediawiki-%s-app-backend.default.svc.cluster.local', $mwVersionForDomain),
            'api' => sprintf('mediawiki-%s-app-api.default.svc.cluster.local', $mwVersionForDomain),
            'alpha' => sprintf('mediawiki-%s-app-alpha.default.svc.cluster.local', $mwVersionForDomain),
        ];
    }

    public function getBackendHostForDomain(string $domain): string {
        // TODO: Make host format configurable for flexibility
        return sprintf('mediawiki-%s-app-backend.default.svc.cluster.local', $this->getMwVersionForDomain($domain));
    }

    private function getMwVersionForDomain(string $domain): string {
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
