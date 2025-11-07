<?php

namespace App\Http\Controllers;

use App\Rules\SettingCaptchaQuestions;
use App\Rules\SettingWikibaseManifestEquivEntities;
use App\WikiSetting;
use Illuminate\Http\Request;

class WikiSettingController extends Controller {
    /**
     * @return (SettingWikibaseManifestEquivEntities|string)[][]
     */
    private function getSettingValidations(): array {
        // FIXME: this list is evil and should be kept in sync with the model in Wiki.php?! (mostly)
        return [
            'wgDefaultSkin' => ['required', 'string', 'in:vector,modern,timeless'],
            'wwExtEnableConfirmAccount' => ['required', 'boolean'],
            'wwExtEnableWikibaseLexeme' => ['required', 'boolean'],
            'wwWikibaseStringLengthString' => ['required', 'integer', 'between:400,2500'],
            'wwWikibaseStringLengthMonolingualText' => ['required', 'integer', 'between:400,2500'],
            'wwWikibaseStringLengthMultilang' => ['required', 'integer', 'between:250,2500'],
            'wikibaseFedPropsEnable' => ['required', 'boolean'],
            'wikibaseManifestEquivEntities' => ['required', 'json', new SettingWikibaseManifestEquivEntities],
            'wwUseQuestyCaptcha' => ['required', 'boolean'],
            'wwCaptchaQuestions' => ['required', 'json', new SettingCaptchaQuestions],
        ];
    }

    /**
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function update($setting, Request $request) {
        $settingValidations = $this->getSettingValidations();

        $request->validate([
            // Allow both internal and external setting names, see normalizeSetting
            'setting' => 'required|string|in:' . implode(',', array_keys($settingValidations)),
        ]);
        $settingName = $request->input('setting');

        $request->validate(['value' => $settingValidations[$settingName]]);
        $value = $request->input('value');
        $wiki = $request->attributes->get('wiki');

        WikiSetting::updateOrCreate(
            [
                'wiki_id' => $wiki->id,
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
