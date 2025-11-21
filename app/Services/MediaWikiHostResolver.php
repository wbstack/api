<?php

namespace App\Services;

use App\Wiki;
use App\Helper;
use App\Helper\WikiDbVersionHelper;
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
     * @throws UnknownDBVersionException
     * @throws UnknownWikiDomainException
     */
    private function getMwVersionForDomain(string $domain): string {
        $wiki = Wiki::where('domain', $domain)->first();

        if (!$wiki) {
            throw new UnknownWikiDomainException("Unknown Wiki Domain '{$domain}'.");
        }

        $dbVersion = $wiki->wikiDb->version;

        if (WikiDbVersionHelper::isValidDbVersion($dbVersion)) {
            return WikiDbVersionHelper::getMwVersion($dbVersion);
        }
        throw new UnknownDBVersionException("Unknown DB version '{$dbVersion}' for domain '{$domain}'.");
    }
}
