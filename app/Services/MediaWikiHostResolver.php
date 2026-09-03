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

        return [
            'web' => $this->getHostForService($mwVersionForDomain, 'web'),
            'backend' => $this->getHostForService($mwVersionForDomain, 'backend'),
            'api' => $this->getHostForService($mwVersionForDomain, 'api'),
            'alpha' => $this->getHostForService($mwVersionForDomain, 'alpha'),
        ];
    }

    public function getBackendHostForDomain(string $domain): string {
        return $this->getHostForService($this->getMwVersionForDomain($domain), 'backend');
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
        return $this->getHostForService($this->getMwVersionForWiki($wiki), 'backend');
    }

    private function getHostForService(string $mwVersion, string $service): string {
        return sprintf('mediawiki-%s-app-%s.default.svc.cluster.local', $mwVersion, $service);
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
