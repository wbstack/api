<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class MediawikiHostMapController extends Controller {
    public function getWikiVersionToHostMapForDomain(Request $request): \Illuminate\Http\JsonResponse
    {
        $domain = $request->query('domain');
        $version = Wiki::where('domain', $domain)
            ->whereNull('deleted_at')
            ->leftJoin('wiki_dbs', 'wiki_id', '=', 'wikis.id')
            ->pluck('version')
            ->first();

        if (is_null($version)) {
            abort(401);
        }
        $mapPath = "";
        if (!file_exists($mapPath)) {
            throw new \Exception("MW host mapping file not found at {$mapPath}");
        }
        $host = "something from $mapPath";

        return response()
            ->json([
                'domain'  => $domain,
                'version' => $version,
                'host'    => $host
            ])
            ->header('x-host', $host)
            ->header('x-version', $version);
    }
}
