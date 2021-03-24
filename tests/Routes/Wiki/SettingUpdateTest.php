<?php

namespace Tests\Routes\Wiki\Managers;

use App\User;
use App\Wiki;
use App\WikiManager;
use App\WikiSetting;
use Tests\TestCase;
use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\Routes\Traits\PostRequestNeedAuthentication;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SettingUpdateTest extends TestCase
{
    protected $route = 'wiki/setting/foo/update';

    use DatabaseTransactions;
    use OptionsRequestAllowed;
    use PostRequestNeedAuthentication;

    public function testSetInvalidSetting()
    {
        $settingName = 'iDoNotExistAsASetting';

        $user = factory(User::class)->create(['verified' => true]);
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

        $user = factory(User::class)->create(['verified' => true]);
        $this->actingAs($user, 'api')
            ->json('POST', str_replace('foo', $settingName, $this->route), [
            'wiki' => 99856,
            'setting' => $settingName,
            'value' => '1',
          ])
          ->assertStatus(401);
    }

    public function provideValidSettings() {
        yield [ 'wgDefaultSkin', 'vector', 'vector' ];
        yield [ 'wwExtEnableConfirmAccount', '1', '1' ];
        yield [ 'wwExtEnableConfirmAccount', '0', '0' ];
        yield [ 'wwWikibaseStringLengthString', '1000', '1000' ];
        yield [ 'wwWikibaseStringLengthMonolingualText', '1000', '1000' ];
        yield [ 'wwWikibaseStringLengthMultilang', '1000', '1000' ];
        yield [ 'wikibaseFedPropsEnable', '1', '1' ];
        yield [ 'wikibaseFedPropsEnable', '0', '0' ];
        yield [ 'wikibaseManifestEquivEntities', json_encode( [] ), json_encode( [] ) ];
        yield [ 'wikibaseManifestEquivEntities', json_encode( [ 'P31' => 'P1' ] ), json_encode( [ 'P31' => 'P1' ] ) ];
        yield [ 'wikibaseManifestEquivEntities', json_encode( [ 'P31' => 'P1', 'Q1' => 'Q1' ] ), json_encode( [ 'P31' => 'P1', 'Q1' => 'Q1' ] ) ];
    }

    /**
     * @dataProvider provideValidSettings
     */
    public function testValidSetting( $settingName, $settingValue, $expectedStored )
    {
        $user = factory(User::class)->create(['verified' => true]);
        $wiki = factory(Wiki::class, 'nodb')->create();
        $manager = factory(WikiManager::class)->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

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

    public function provideValidSettingsBadValues() {
        yield [ 'wgDefaultSkin', 'foo' ];
        yield [ 'wwExtEnableConfirmAccount', 'foo' ];
        yield [ 'wwWikibaseStringLengthString', 12 ];
        yield [ 'wwWikibaseStringLengthMonolingualText', 12 ];
        yield [ 'wwWikibaseStringLengthMultilang', 12 ];
        yield [ 'wikibaseFedPropsEnable', 'foo' ];
        yield [ 'wikibaseManifestEquivEntities', 'foo' ];
        yield [ 'wikibaseManifestEquivEntities', json_encode( [ 'P1', 'P2' ] ) ];
        yield [ 'wikibaseManifestEquivEntities', json_encode( [ 'P1' => 'P2', 'P3' => 'aa' ] ) ];
        yield [ 'wikibaseManifestEquivEntities', json_encode( [ 'P1' => 'P2', 'aa' => 'Q2' ] ) ];
        yield [ 'wikibaseManifestEquivEntities', json_encode( [ 'P1' => 'P2', 'P10' => 'Q2' ] ) ];
    }

    /**
     * @dataProvider provideValidSettingsBadValues
     */
    public function testValidSettingBadValues( $settingName, $settingValue )
    {
        $user = factory(User::class)->create(['verified' => true]);

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