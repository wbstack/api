<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Wiki;
use Illuminate\Http\Request;

class WikiController extends Controller {
    private static $with = ['wikiDb', 'wikiQueryserviceNamespace', 'settings'];

    public function getWikiForDomain(Request $request): \Illuminate\Http\JsonResponse {
        $domain = $request->input('domain');

        // XXX: this same logic is in quickstatements.php and platform api WikiController backend
        try {
            if ($domain === 'localhost' || $domain === 'mediawiki') {
                // If just using localhost then just get the first undeleted wiki
                $result = Wiki::with(self::$with)->first();
            } else {
                // TODO don't select the timestamps and redundant info for the settings?
                $result = Wiki::where('domain', $domain)->with(self::$with)->first();
            }
        } catch (\Exception $ex) {
            return response()->json($ex->getMessage(), 500);
        }

        if (! $result) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json(['data' => $result], 200);
    }
}
