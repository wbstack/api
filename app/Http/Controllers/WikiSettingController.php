<?php

namespace App\Http\Controllers;

use App\Rules\SettingWikibaseManifestEquivEntities;
use App\WikiManager;
use App\WikiSetting;
use Illuminate\Http\Request;

class WikiSettingController extends Controller
{
    /**
     * @return (SettingWikibaseManifestEquivEntities|string)[][]
     *
     * @psalm-return array{wgDefaultSkin: array{0: 'required', 1: 'string', 2: 'in:vector,modern,timeless'}, wwExtEnableConfirmAccount: array{0: 'required', 1: 'boolean'}, wwExtEnableWikibaseLexeme: array{0: 'required', 1: 'boolean'}, wwWikibaseStringLengthString: array{0: 'required', 1: 'integer', 2: 'between:400,2500'}, wwWikibaseStringLengthMonolingualText: array{0: 'required', 1: 'integer', 2: 'between:400,2500'}, wwWikibaseStringLengthMultilang: array{0: 'required', 1: 'integer', 2: 'between:250,2500'}, wikibaseFedPropsEnable: array{0: 'required', 1: 'boolean'}, wikibaseManifestEquivEntities: array{0: 'required', 1: 'json', 2: SettingWikibaseManifestEquivEntities}}
     */
    private function getSettingValidations(): array
    {
        // FIXME: this list is evil and should be kept in sync with the model in Wiki.php?! (mostly)
        return [
            'wgDefaultSkin' => ['required', 'string', 'in:vector,modern,timeless'],
            'wwExtEnableConfirmAccount' => ['required', 'boolean'],
            'wwExtEnableWikibaseLexeme' => ['required', 'boolean'],
            'wwWikibaseStringLengthString' => ['required', 'integer', 'between:400,2500'],
            'wwWikibaseStringLengthMonolingualText' => ['required', 'integer', 'between:400,2500'],
            'wwWikibaseStringLengthMultilang' => ['required', 'integer', 'between:250,2500'],
            'wikibaseFedPropsEnable' => ['required', 'boolean'],
            'wikibaseManifestEquivEntities' => ['required', 'json', new SettingWikibaseManifestEquivEntities()],
        ];
    }

    /**
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function update($setting, Request $request)
    {
        $settingValidations = $this->getSettingValidations();

        $request->validate([
            'wiki' => 'required|numeric',
            // Allow both internal and external setting names, see normalizeSetting
            'setting' => 'required|string|in:'.implode(',', array_keys($settingValidations)),
        ]);
        $settingName = $request->input('setting');

        $request->validate(['value' => $settingValidations[$settingName]]);
        $value = $request->input('value');

        $user = $request->user();
        $wikiId = $request->input('wiki');
        $userId = $user->id;

        // Check that the requesting user manages the wiki
        // TODO turn this into a generic guard for all of these types of routes...
        if (WikiManager::where('user_id', $userId)->where('wiki_id', $wikiId)->count() !== 1) {
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
