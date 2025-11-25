<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Wiki;
use Illuminate\Http\Request;

class WikiDbVersionController extends Controller {
    // keep in sync with App\Services\MediaWikiHostResolver
    private const DB_VERSION_TO_MW_VERSION = [
        'mw1.39-wbs1' => '139',
        'mw1.43-wbs1' => '143',
    ];

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

            if (!array_key_exists($targetDbVersion, self::DB_VERSION_TO_MW_VERSION)) {
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
