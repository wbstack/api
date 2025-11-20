<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\MediaWikiHostResolver;
use App\Services\UnknownDBVersionException;
use App\Services\UnknownWikiDomainException;
use Illuminate\Http\Request;

class MediaWikiHostsController extends Controller {
    public function getWikiHostsForDomain(Request $request): \Illuminate\Http\JsonResponse {
        $mediawikiHostResolver = new MediaWikiHostResolver;
        $domain = $request->query('domain');
        try {
            $hosts = $mediawikiHostResolver->getHostsForDomain($domain);
        } catch (UnknownWikiDomainException $e) {
            return response()->json(['error' => 'Domain not found.'], 404);
        } catch (UnknownDBVersionException $e) {
            return response()->json(['error' => 'Unknown database version.'], 500);
        }

        return response()
            ->json([
                'domain' => $domain,
                'backend-host' => $hosts['backend'],
                'web-host' => $hosts['web'],
                'api-host' => $hosts['api'],
                'alpha-host' => $hosts['alpha'],
            ])
            ->header('x-backend-host', $hosts['backend'])
            ->header('x-web-host', $hosts['web'])
            ->header('x-api-host', $hosts['api'])
            ->header('x-alpha-host', $hosts['alpha']);
    }
}
