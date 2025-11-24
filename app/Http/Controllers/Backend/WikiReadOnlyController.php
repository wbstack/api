<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Wiki;
use Illuminate\Http\Request;

class WikiReadOnlyController extends Controller {
    public function setWikiReadOnly(Request $request) {

        $validated = $request->validate([
            'domain' => 'required|string',
        ]);

        $domain = $validated['domain'];
        $wiki = Wiki::where('domain', $domain)->first();

        if (!$wiki) {
            return response()->json([
                'error' => 'Wiki not found for domain: ' . $domain,
            ], 404);
        }

        $wiki->setSetting('wgReadOnly', 'This wiki is currently read-only.');

        return response()->json([
            'success' => true,
            'domain' => $domain,
            'message' => 'Wiki set to read-only successfully.',
        ]);
    }
}
