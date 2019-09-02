<?php

namespace App\Http\Controllers\Backend;

use App\Wiki;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class WikiController extends Controller
{
    public function getWikiForDomain(Request $request)
    {
        $domain = $request->input('domain');

        if ($domain === 'localhost') {
            // TODO if not in debug mode dont allow this code path to run
            $result = Wiki::with(['wikiDb'])->first();
        } else {
            $result = Wiki::where('domain', $domain)->with(['wikiDb'])->first();
        }

        $res['success'] = true;
        $res['data'] = $result;

        return response($res);
    }
}
