<?php

namespace App\Http\Controllers;

use App\WikiManager;
use App\WikiSetting;
use App\Rules\SettingWikibaseManifestEquivEntities;
use Illuminate\Http\Request;

class WikiSettingController extends Controller
{

    private function getSettingValidations() {
        return [
            'wgDefaultSkin' => [ 'required', 'string', 'in:vector,modern,timeless' ],
            'wwExtEnableConfirmAccount' => [ 'required', 'boolean' ],
            'wwWikibaseStringLengthString' => [ 'required', 'integer', 'between:400,2500' ],
            'wwWikibaseStringLengthMonolingualText' => [ 'required', 'integer', 'between:400,2500' ],
            'wwWikibaseStringLengthMultilang' => [ 'required', 'integer', 'between:250,2500' ],
            'wikibaseFedPropsEnable' => [ 'required', 'boolean' ],
            'wikibaseManifestEquivEntities' => [ 'required', 'json', new SettingWikibaseManifestEquivEntities() ],
        ];
    }

    public function update( $setting, Request $request)
    {
        $settingValidations = $this->getSettingValidations();

        $request->validate([
            'wiki' => 'required|numeric',
            // Allow both internal and external setting names, see normalizeSetting
            'setting' => 'required|string|in:' . implode( ',', array_keys( $settingValidations ) ),
        ]);
        $settingName = $request->input('setting');

        $request->validate([ 'value' => $settingValidations[$settingName]]);
        $value = $request->input('value');

        $user = $request->user();
        $wikiId = $request->input('wiki');
        $userId = $user->id;

        // Check that the requesting user manages the wiki
        // TODO turn this into a generic guard for all of these types of routes...
        if( WikiManager::where( 'user_id', $userId )->where( 'wiki_id', $wikiId )->count() !== 1 ) {
            // The deletion was requested by a user that does not manage the wiki
            return response()->json('Unauthorized', 401);
        }

        WikiSetting::updateOrCreate(
            [
                'wiki_id' => $wikiId,
                'name' => $settingName,
            ],
            [
                'value' => $value,
            ]
        );

        $res['success'] = true;
        return response($res);
    }
}
