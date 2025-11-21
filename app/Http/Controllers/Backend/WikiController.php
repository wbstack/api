<?php

namespace App\Http\Controllers\Backend;

use App\Helper\WikiDbVersionHelper;
use App\Http\Controllers\Controller;
use App\Wiki;
use Illuminate\Http\Request;

class WikiController extends Controller {
    private static $with = ['wikiDb', 'wikiQueryserviceNamespace', 'settings'];

    public function getWikiForDomain(Request $request): \Illuminate\Http\JsonResponse {
        $validated = $request->validate([
            'domain' => 'required|string',
        ]);

        $domain = $validated['domain'];

        // XXX: this same logic is in quickstatements.php and platform api WikiController backend
        try {
            $result = Wiki::with(['wikiDb', 'wikiQueryserviceNamespace', 'settings'])->firstWhere('domain', $domain);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }

        if (!$result) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json(['data' => $result], 200);
    }

    public function setWikiDbVersionForDomain(Request $request): \Illuminate\Http\JsonResponse {
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

            if (!WikiDbVersionHelper::isValidDbVersion($targetDbVersion)) {
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
