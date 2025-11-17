<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\MediaWikiHostResolver;
use App\Services\UnknownDBVersionException;
use App\Services\UnknownWikiDomainException;
use Illuminate\Http\Request;

class MediaWikiHostController extends Controller {
    public function getWikiHostForDomain(Request $request): \Illuminate\Http\JsonResponse {
        $mediawikiHostResolver = new MediaWikiHostResolver;
        $domain = $request->query('domain');
        try {
            $backendHost = $mediawikiHostResolver->getBackendHostForDomain($domain);
            $webHost = $mediawikiHostResolver->getWebHostForDomain($domain);
            $apiHost = $mediawikiHostResolver->getApiHostForDomain($domain);
        } catch (UnknownWikiDomainException $e) {
            return response()->json(['error' => 'Domain not found.'], 404);
        } catch (UnknownDBVersionException $e) {
            return response()->json(['error' => 'Unknown database version.'], 500);
        }

        return response()
            ->json([
                'domain' => $domain,
                'backend-host' => $backendHost,
                'web-host' => $webHost,
                'api-host' => $apiHost,
            ])
            ->header('x-backend-host', $backendHost)
            ->header('x-web-host', $webHost)
            ->header('x-api-host', $apiHost);
    }
}
