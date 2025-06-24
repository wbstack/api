<?php

namespace App\Http\Controllers;

use App\Jobs\SetWikiLogo;
use App\WikiSetting;
use Illuminate\Http\Request;

class WikiLogoController extends Controller
{
    /**
     * It would be beneficial to have a bit of atomicness here?
     * Right now WgLogo is always the same path when set, so if we start writing new files but die we still end up updating the site.
     * Fine for now but...
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $request->validate([
            'logo' => 'required|mimes:png',
        ]);

        $wiki = $request->attributes->get('wiki');

        // run the job to set the wiki logo
        ( new SetWikiLogo('id', $wiki->id, $request->file('logo')->getRealPath()) )->handle();

        // get the logo URL from the settings
        $wgLogoSetting = $wiki->settings()->firstWhere(['name' => WikiSetting::wgLogo])->value;

        $res['success'] = true;
        $res['url'] = $wgLogoSetting;

        return response($res);
    }
}
