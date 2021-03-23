<?php

namespace App\Http\Controllers;

use App\WikiManager;
use App\WikiSetting;
use Illuminate\Http\Request;

class WikiSettingController extends Controller
{

    /**
     * Keys are API setting names
     * Values are mediawiki setting names
     *
     * FIXME: this list probably also needs to be kept in sync with the one in Wiki.php model
     *
     * @var string[]
     */
    static $settingMap = [
        'skin' => 'wgDefaultSkin',
        'extConfirmAccount' => 'wwExtEnableConfirmAccount',
        'wikibaseStringLengthString' => 'wwWikibaseStringLengthString',
        'wikibaseStringLengthMonolingualText' => 'wwWikibaseStringLengthMonolingualText',
        'wikibaseStringLengthMultilang' => 'wwWikibaseStringLengthMultilang',
        ];

    static $settingValidation = [
        'skin' => 'string|in:vector,modern,timeless',
        'extConfirmAccount' => 'boolean',
        'wikibaseStringLengthString' => 'integer|between:400,2500',
        'wikibaseStringLengthMonolingualText' => 'integer|between:400,2500',
        'wikibaseStringLengthMultilang' => 'integer|between:250,2500',
    ];

    public function update( $setting, Request $request)
    {
        $request->validate([
            'wiki' => 'required|numeric',
            'setting' => 'required|string|in:' . implode( ',', array_keys( self::$settingMap ) ),
        ]);
        $apiSetting = $request->input('setting');

        // Only allow certain values
        $request->validate([
            'value' => 'required' .( array_key_exists( $apiSetting, self::$settingValidation ) ? '|' . self::$settingValidation[$apiSetting] : '' ),
        ]);
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
                'name' => self::$settingMap[$apiSetting],
            ],
            [
                'value' => $value,
            ]
        );

        $res['success'] = true;
        return response($res);
    }
}
