<?php

namespace App\Http\Controllers;

use App\WikiManager;
use App\WikiSetting;
use App\Rules\SettingWikibaseManifestEquivEntities;
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
        'wikibaseFedPropsEnable' => 'wikibaseFedPropsEnable',
        'wikibaseManifestEquivEntities' => 'wikibaseManifestEquivEntities',
        ];

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
        $settingValidations = $this->getSettingValidations();

        $request->validate([
            'wiki' => 'required|numeric',
            // Allow both internal and external setting names, see normalizeSetting
            'setting' => 'required|string|in:' . implode( ',', array_unique( array_merge( array_keys( self::$oldSettingMap ), array_keys( $settingValidations ) ) ) ),
        ]);
        $settingName = $this->normalizeSetting( $request->input('setting') );

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
