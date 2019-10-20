<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Wiki;
use Illuminate\Http\Request;

class WikiController extends Controller
{
    public function getWikiForDomain(Request $request)
    {
        $domain = $request->input('domain');

        // If we are developing and the request is localhost or mediawiki then return the settings of the first wiki :)
        if ($domain === 'localhost' || $domain === 'mediawiki') {
            // TODO if not in debug mode don't allow this code path to run
            $result = Wiki::with(['wikiDb','wikiQueryserviceNamespace','settings'])->first();
        } else {
            // TODO don't select the timestamps and redundant info for the settings?
            $result = Wiki::where('domain', $domain)->with(['wikiDb','wikiQueryserviceNamespace','settings'])->first();
        }

        $res['success'] = true;
        $res['data'] = $result;

        return response($res);
    }
}
