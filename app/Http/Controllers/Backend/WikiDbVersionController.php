<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Wiki;
use Illuminate\Http\Request;

class WikiDbVersionController extends Controller {
    public function updateWikiDbVersion(Request $request): \Illuminate\Http\JsonResponse {
        $validated = $request->validate([
            'domain' => 'required|string',
            'dbVersion' => 'required|string',
        ]);

        $domain = $validated['domain'];
        $targetDbVersion = $validated['dbVersion'];

        try {
            $wiki = Wiki::with('wikiDb')->firstWhere('domain', $domain);

            if (!$wiki) {
                return response()->json(['error' => "No wiki found with domain: '{$domain}'"], 404);
            }

            if (!array_key_exists($targetDbVersion, config('mw-db-version-map'))) {
                return response()->json(['error' => "Invalid database version string: '{$targetDbVersion}'"], 400);
            }

            $wiki->wikiDb->version = $targetDbVersion;
            $wiki->wikiDb->save();
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }

        return response()->json(['result' => 'success'], 200);
    }
}
