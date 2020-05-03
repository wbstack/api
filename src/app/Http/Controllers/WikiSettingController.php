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
     * @var string[]
     */
    static $settingMap = [
        'skin' => 'wgDefaultSkin'
        ];
    static $settingAllowedValues = [
        'skin' => [ 'vector', 'modern', 'timeless' ]
    ];

    public function update( $setting, Request $request)
    {
        // Only allow setting skin...
        $request->validate([
            'wiki' => 'required|numeric',
            'setting' => 'required|string|in:' . implode( ',', array_keys( self::$settingMap ) ),
        ]);
        $apiSetting = $request->input('setting');

        // Only allow certain values
        $request->validate([
            'value' => 'required|string|in:' . implode( ',', self::$settingAllowedValues[$apiSetting] ),
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
