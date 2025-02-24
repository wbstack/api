<?php

namespace Tests\Routes\Wiki\Managers;

use App\User;
use App\Wiki;
use App\WikiManager;
use App\WikiSetting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\Routes\Traits\PostRequestNeedAuthentication;
use Tests\TestCase;
use Database\Factories\UserFactory;

class SettingUpdateTest extends TestCase
{
    use HasFactory;

    protected $route = 'wiki/setting/foo/update';

    use DatabaseTransactions;
    use OptionsRequestAllowed;
    use PostRequestNeedAuthentication;

    public function testSetInvalidSetting()
    {
        $settingName = 'iDoNotExistAsASetting';

        $user = User::factory()->create(['verified' => true]);
        $this->actingAs($user, 'api')
            ->json('POST', str_replace('foo', $settingName, $this->route), [
            'wiki' => 1,
            'setting' => $settingName,
            'value' => '1',
          ])
          ->assertStatus(422)
          ->assertJsonStructure(['errors' => ['setting']]);
    }

    public function testValidSettingNoWiki()
    {
        $settingName = 'wwExtEnableConfirmAccount';

        $user = User::factory()->create(['verified' => true]);
        $this->actingAs($user, 'api')
            ->json('POST', str_replace('foo', $settingName, $this->route), [
            'wiki' => 99856,
            'setting' => $settingName,
            'value' => '1',
          ])
          ->assertStatus(401);
    }

    static public function provideValidSettings()
    {
        yield ['wgDefaultSkin', 'vector', 'vector'];
        yield ['wwExtEnableConfirmAccount', '1', '1'];
        yield ['wwExtEnableConfirmAccount', '0', '0'];
        yield ['wwWikibaseStringLengthString', '1000', '1000'];
        yield ['wwWikibaseStringLengthMonolingualText', '1000', '1000'];
        yield ['wwWikibaseStringLengthMultilang', '1000', '1000'];
        yield ['wikibaseFedPropsEnable', '1', '1'];
        yield ['wikibaseFedPropsEnable', '0', '0'];

        $emptyArrays = json_encode(['items' => [], 'properties'=> []]);
        yield ['wikibaseManifestEquivEntities', $emptyArrays, $emptyArrays];

        $somePropsNoItems = json_encode(['properties' => ['P31' => 'P1'], 'items' => []]);
        yield ['wikibaseManifestEquivEntities', $somePropsNoItems, $somePropsNoItems];

        $validProps = json_encode(['properties' => ['P31' => 'P1'], 'items' => ['Q1' => 'Q1']]);
        yield ['wikibaseManifestEquivEntities', $validProps, $validProps];

        yield ['wwUseQuestyCaptcha', '1', '1'];
        yield ['wwUseQuestyCaptcha', '0', '0'];
        $validCaptchaQuestions = json_encode([
            "How many vowels are in this question?" => ['12', 'twelve'],
            "What is the chemical formula of water" => ['H2O'],
            "2 + 4 = ?" => ['6', 'six']
        ]);
        yield ['wwCaptchaQuestions', $validCaptchaQuestions, $validCaptchaQuestions ];
    }

    /**
     * @dataProvider provideValidSettings
     */
    public function testValidSetting($settingName, $settingValue, $expectedStored)
    {
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory('nodb')->create();
        $manager = WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        $this->actingAs($user, 'api')
            ->json('POST', str_replace('foo', $settingName, $this->route), [
            'wiki' => $wiki->id,
            'setting' => $settingName,
            'value' => $settingValue,
            ])
            ->assertStatus(200);

        $this->assertSame(
            $expectedStored,
              WikiSetting::whereWikiId($wiki->id)->whereName($settingName)->first()->value
          );
    }

    static public function provideValidSettingsBadValues()
    {
        yield ['wgDefaultSkin', 'foo'];
        yield ['wwExtEnableConfirmAccount', 'foo'];
        yield ['wwWikibaseStringLengthString', 12];
        yield ['wwWikibaseStringLengthMonolingualText', 12];
        yield ['wwWikibaseStringLengthMultilang', 12];
        yield ['wikibaseFedPropsEnable', 'foo'];
        yield ['wikibaseManifestEquivEntities', 'foo'];

        // props without mapping
        yield ['wikibaseManifestEquivEntities', json_encode(['properties' => ['P1', 'P2'], 'items' => []])];
        // invalid property id (right side)
        yield ['wikibaseManifestEquivEntities', json_encode(['properties' => ['P1' => 'P2', 'P3' => 'aa'], 'items' => []])];
        // invalid property id (left side)
        yield ['wikibaseManifestEquivEntities', json_encode(['properties' => ['P1' => 'P2', 'aa' => 'P3'], 'items' => []])];
        // invalid entity type
        yield ['wikibaseManifestEquivEntities', json_encode(['foo' => []])];
        // mismatch entitytypes
        yield ['wikibaseManifestEquivEntities', json_encode(['properties' => ['P1' => 'P2'], 'items' => ['P10' => 'Q2']])];
        // all entities should be of the same type
        yield ['wikibaseManifestEquivEntities', json_encode(['properties' => ['P1' => 'P2'], 'items' => ['Q2' => 'Q2', 'P2' => 'P2']])];
        // no answers
        yield ['wwCaptchaQuestions', json_encode([
            "How many vowels are in this question?" => []
        ])];
        // Question too long
        yield ['wwCaptchaQuestions', json_encode([
            "How many vowels are in this question?How many vowels are in this question?
            How many vowels are in this question?How many vowels are in this question?
            How many vowels are in this question?How many vowels are in this question?
            How many vowels are in this question?How many vowels are in this question?" => ['12', 'twelve']
        ])];
        // Answer too long
        yield ['wwCaptchaQuestions', json_encode([
            "Is this too long?" => [
                "LongAnswerLongAnswerLongAnswerLongAnswerLongAnswerLongAnswerLongAnswer
                LongAnswerLongAnswerLongAnswerLongAnswerLongAnswerLongAnswerLongAnswer
                LongAnswerLongAnswerLongAnswerLongAnswerLongAnswerLongAnswerLongAnswer
                LongAnswerLongAnswerLongAnswerLongAnswerLongAnswerLongAnswerLongAnswer"
            ]
        ])];
    }

    /**
     * @dataProvider provideValidSettingsBadValues
     */
    public function testValidSettingBadValues($settingName, $settingValue)
    {
        $user = User::factory()->create(['verified' => true]);

        $this->actingAs($user, 'api')
            ->json('POST', str_replace('foo', $settingName, $this->route), [
            'wiki' => 1,
            'setting' => $settingName,
            'value' => $settingValue,
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['value']]);
    }
}
