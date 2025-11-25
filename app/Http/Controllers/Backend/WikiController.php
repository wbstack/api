<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Wiki;
use Illuminate\Http\Request;

class WikiController extends Controller {
    public function getWikiForDomain(Request $request): \Illuminate\Http\JsonResponse {
        $validated = $request->validate([
            'domain' => 'required|string',
        ]);

        $domain = $validated['domain'];
        try {
            $wiki = Wiki::with(['wikiDb', 'wikiQueryserviceNamespace', 'settings'])->firstWhere('domain', $domain);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }

        if (!$wiki) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json(['data' => $wiki], 200);
    }
}
