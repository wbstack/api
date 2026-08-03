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

    /**
     * Laravel Http or Guzzle callers need a full backend URL.
     */
    public function getBackendUrlForDomain(string $domain): string {
        return 'http://' . $this->getBackendHostForDomain($domain);
    }

    public function getBackendUrlForWiki($wiki): string {
        return 'http://' . $this->getBackendHostForWiki($wiki);
    }

    public function getBackendHostForWiki($wiki): string {
        // TODO: Make host format configurable for flexibility
        return sprintf('mediawiki-%s-app-backend.default.svc.cluster.local', $this->getMwVersionForWiki($wiki));
    }

    private function getMwVersionForDomain(string $domain): string {
        $wiki = Wiki::where('domain', $domain)->first();

        if (!$wiki) {
            throw new UnknownWikiDomainException("Unknown Wiki Domain '{$domain}'.");
        }

        return $this->getMwVersionForWiki($wiki);
    }

    private function getMwVersionForWiki($wiki): string {
        $dbVersion = $wiki->wikiDb?->version;

        if ($dbVersion === null) {
            throw new UnknownDBVersionException("Unknown DB version for domain '{$wiki->domain}'.");
        }

        $versionMap = config('mw-db-version-map');
        if (array_key_exists($dbVersion, $versionMap)) {
            return $versionMap[$dbVersion];
        }
        throw new UnknownDBVersionException("Unknown DB version '{$dbVersion}' for domain '{$wiki->domain}'.");
    }
}
