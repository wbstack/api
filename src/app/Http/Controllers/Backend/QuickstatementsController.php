<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Wiki;
use Illuminate\Http\Request;

class QuickstatementsController extends Controller
{
    private static $with = ['wikiDb','wikiQueryserviceNamespace','settings'];

    public function getJsonConfigForWikiDomain(Request $request)
    {
        $domain = $request->input('domain');

        $a = [

        ];

        // TODO don't do the localhost checks when in production? :)
        if ( substr($domain,-10, 10) === '.localhost' ){
            // localhost development, with a full domain prefixing .localhost
            // eg. wiki.addshore.com.localhost
            $result = Wiki::where('domain', substr($domain,0, -10))->with(self::$with)->first();
        } else if ( $domain === 'localhost' || $domain === 'mediawiki' ) {
            // If just using localhost then just get the first undeleted wiki
            // TODO actually check this doesn't show deleted wikis?
            $result = Wiki::with(self::$with)->first();
        } else {
            // TODO don't select the timestamps and redundant info for the settings?
            $result = Wiki::where('domain', $domain)->with(self::$with)->first();
        }

        $res['success'] = true;
        $res['data'] = $result;

        return response($res);
    }
}
