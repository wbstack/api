<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class MediawikiHostMapController extends Controller {
    public function getWikiVersionToHostMapForDomain(Request $request): \Illuminate\Http\JsonResponse
    {
        $domain = $request->query('domain');
        $wikiDbVersion = Wiki::where('domain', $domain)
            ->whereNull('deleted_at')
            ->leftJoin('wiki_dbs', 'wiki_id', '=', 'wikis.id')
            ->pluck('version')
            ->first();

        if (is_null($wikiDbVersion)) {
            abort(401);
        }
        $mwDbToHostMap = [
            'mw1.39-wbs1' => '139-app',
            'mw1.43-wbs1' => '143-app'
        ];

        return response()
            ->json([
                'domain'  => $domain,
                'version' => $wikiDbVersion,
                'host'    => $mwDbToHostMap[$wikiDbVersion],
            ])
            ->header('x-host', $mwDbToHostMap[$wikiDbVersion])
            ->header('x-version', $wikiDbVersion);
    }
}
