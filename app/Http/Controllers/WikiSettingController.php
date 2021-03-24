<?php

namespace App\Http\Controllers;

use App\WikiManager;
use App\WikiSetting;
use Illuminate\Http\Request;

class WikiSettingController extends Controller
{

    /**
     * An map of old setting names used externally to the internal names we now use.
     * See normalizeSetting
     * Use of the old names (the keys here) should be deprecated
     * We can remove this once we update the UI to not used them any more.
     *
     * FIXME: this list probably also needs to be kept in sync with the one in Wiki.php model
     *
     * @var string[]
     */
    static $oldSettingMap = [
        'skin' => 'wgDefaultSkin',
        'extConfirmAccount' => 'wwExtEnableConfirmAccount',
        'wikibaseStringLengthString' => 'wwWikibaseStringLengthString',
        'wikibaseStringLengthMonolingualText' => 'wwWikibaseStringLengthMonolingualText',
        'wikibaseStringLengthMultilang' => 'wwWikibaseStringLengthMultilang',
        ];

    static $settingValidation = [
        'wgDefaultSkin' => 'string|in:vector,modern,timeless',
        'wwExtEnableConfirmAccount' => 'boolean',
        'wwWikibaseStringLengthString' => 'integer|between:400,2500',
        'wwWikibaseStringLengthMonolingualText' => 'integer|between:400,2500',
        'wwWikibaseStringLengthMultilang' => 'integer|between:250,2500',
    ];

    /**
     * Historically the setting names submitted to the API and actually stored were different.
     * We want to standardize these to make things easier to work with.
     * (especially now we are also retrieving these settings)
     * So now accept both the old external names and internal names, but internally convert to the internal names.
     */
    private function normalizeSetting( $setting ) {
        if ( array_key_exists( $setting, self::$oldSettingMap ) ) {
            $setting = self::$oldSettingMap[$setting];
        }
        return $setting;
    }

    public function update( $setting, Request $request)
    {
        $request->validate([
            'wiki' => 'required|numeric',
            // Allow both internal and external setting names, see normalizeSetting
            'setting' => 'required|string|in:' . implode( ',', array_merge( array_keys( self::$oldSettingMap ), self::$oldSettingMap ) ),
        ]);
        $settingName = $this->normalizeSetting( $request->input('setting') );

        $request->validate([
            'value' => 'required' . ( array_key_exists( $settingName, self::$settingValidation ) ? '|' . self::$settingValidation[$settingName] : '' ),
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
