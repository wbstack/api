<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Wiki;
use Illuminate\Http\Request;

class IngressController extends Controller
{
    public function getWikiVersionForDomain(Request $request): \Illuminate\Http\Response
    {
        $domain = $request->query('domain');
        $version = Wiki::where('domain', $domain)
            ->leftJoin('wiki_dbs', 'wiki_id', '=', 'wikis.id')
            ->pluck('version')
            ->first();

        return response(1)->header('x-version', $version);
    }
}
