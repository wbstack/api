<?php

namespace App\Http\Controllers;

use App\Jobs\SetWikiLogo;
use App\Wiki;
use App\WikiManager;
use App\WikiSetting;
use Illuminate\Http\Request;
use App\Helper\StorageHelper;

class WikiLogoController extends Controller
{
    /**
     * It would be beneficial to have a bit of atomicness here?
     * Right now WgLogo is always the same path when set, so if we start writing new files but die we still end up updating the site.
     * Fine for now but...
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function update(Request $request, StorageHelper $storageHelper)
    {
        $request->validate([
            'wiki' => 'required|numeric',
            'logo' => 'required|mimes:png',
        ]);

        $user = $request->user();

        $wikiId = $request->input('wiki');
        $userId = $user->id;

        // Check that the requesting user manages the wiki
        // TODO turn this into a generic guard for all of these types of routes...
        if (WikiManager::where('user_id', $userId)->where('wiki_id', $wikiId)->count() !== 1) {
            // The logo update was requested by a user that does not manage the wiki
            return response()->json('Unauthorized', 401);
        }

        // run the job to set the wiki logo
        ( new SetWikiLogo('id', $wikiId, $request->file('logo')->getRealPath()) )->handle($storageHelper);

        // get the logo URL from the settings
        $wiki = Wiki::find($wikiId);
        $wgLogoSetting = $wiki->settings()->firstWhere(['name' => WikiSetting::wgLogo])->value;

        $res['success'] = true;
        $res['url'] = $wgLogoSetting;

        return response($res);
    }
}
