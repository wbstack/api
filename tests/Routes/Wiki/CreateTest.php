<?php

namespace Tests\Routes\Wiki\Managers;

use App\Jobs\ElasticSearchAliasInit;
use App\Jobs\MediawikiInit;
use App\Jobs\ProvisionWikiDbJob;
use App\QueryserviceNamespace;
use App\User;
use App\Wiki;
use App\WikiManager;
use App\WikiProfile;
use App\WikiSetting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;

class CreateTest extends TestCase {
    protected $route = 'wiki/create';

    const defaultData = [
        'domain' => 'dErP.com',
        'sitename' => 'merp',
        'username' => 'AdminBoss',
        'profile' => '{
                        "audience": "narrow",
                        "temporality": "permanent",
                        "purpose": "data_hub"
                      }',
    ];

    use DatabaseTransactions;
    use OptionsRequestAllowed;

    /**
     * @dataProvider createDispatchesSomeJobsProvider
     */
    public function testWikiCreateDispatchesSomeJobs($elasticSearchConfig) {
        $enabledForNewWikis = $elasticSearchConfig['enabledForNewWikis'];
        $sharedIndexHost = $elasticSearchConfig['sharedIndexHost'] ?? null;
        $sharedIndexPrefix = $elasticSearchConfig['sharedIndexPrefix'] ?? null;

        Config::set('wbstack.elasticsearch_enabled_by_default', $enabledForNewWikis);
        Config::set('wbstack.elasticsearch_shared_index_host', $sharedIndexHost);
        Config::set('wbstack.elasticsearch_shared_index_prefix', $sharedIndexPrefix);

        $this->createSQLandQSDBs();

        Queue::fake();

        $user = User::factory()->create(['verified' => true]);
        Queue::assertNothingPushed();

        $response = $this->actingAs($user, 'api')
            ->json(
                'POST',
                $this->route,
                [
                    'domain' => 'dErP.com',
                    'sitename' => 'merp',
                    'username' => 'AdminBoss',
                    'profile' => '{
                                "audience": "narrow",
                                "temporality": "permanent",
                                "purpose": "data_hub"
                              }',
                ]
            );

        if ($enabledForNewWikis && $sharedIndexHost && $sharedIndexPrefix) {
            Queue::assertPushed(ElasticSearchAliasInit::class, 1);
        } else {
            Queue::assertNotPushed(ElasticSearchAliasInit::class);
        }

        if ($enabledForNewWikis && !($sharedIndexHost && $sharedIndexPrefix)) {
            $response->assertStatus(503)
                ->assertJsonPath('message', 'Search enabled, but its configuration is invalid');

            Queue::assertNotPushed(ProvisionWikiDbJob::class);
            Queue::assertNotPushed(MediawikiInit::class);
        } else {
            $response->assertStatus(200)
                ->assertJsonPath('data.domain', 'derp.com')
                ->assertJsonPath('data.name', null)
                ->assertJsonPath('success', true);

            Queue::assertPushed(ProvisionWikiDbJob::class, 1);
            Queue::assertPushed(MediawikiInit::class, 1);

            $id = $response->original['data']['id'];

            $this->assertSame(
                1,
                WikiSetting::where(['name' => WikiSetting::wgSecretKey, 'wiki_id' => $id])->count()
            );

            $this->assertSame(
                1,
                WikiSetting::where(['name' => WikiSetting::wwExtEnableElasticSearch, 'value' => $enabledForNewWikis, 'wiki_id' => $id])->count()
            );
        }
    }

    public static function createDispatchesSomeJobsProvider() {
        yield [[
            'enabledForNewWikis' => true,
            'sharedIndexHost' => 'somehost',
            'sharedIndexPrefix' => 'testing_1',
        ]];

        yield [[
            'enabledForNewWikis' => true,
            'sharedIndexPrefix' => 'testing_1',
        ]];

        yield [[
            'enabledForNewWikis' => true,
        ]];

        yield [[
            'enabledForNewWikis' => false,
        ]];
    }

    public function testCreateWikiLimitsNumWikisPerUser() {
        $manager = $this->app->make('db');

        $job1 = new ProvisionWikiDbJob;
        $job1->handle($manager);

        $job2 = new ProvisionWikiDbJob;
        $job2->handle($manager);

        QueryserviceNamespace::create([
            'namespace' => 'ns-1',
            'backend' => 'wdqs.svc',
        ]);
        QueryserviceNamespace::create([
            'namespace' => 'ns-2',
            'backend' => 'wdqs.svc',
        ]);

        Config::set('wbstack.wiki_max_per_user', 1);

        Queue::fake();

        $user = User::factory()->create(['verified' => true]);
        Queue::assertNothingPushed();

        // This shouldn't stop first create since it's deleted
        $this->wiki = Wiki::factory()->create(['deleted_at' => Carbon::now()->timestamp]);
        WikiManager::factory()->create(['wiki_id' => $this->wiki->id, 'user_id' => $user->id]);

        $response = $this->actingAs($user, 'api')
            ->json(
                'POST',
                $this->route,
                [
                    'domain' => 'mywikidomain.com',
                    'sitename' => 'merp',
                    'username' => 'AdminBoss',
                ]
            );

        $response->assertStatus(200)
            ->assertJsonPath('data.domain', 'mywikidomain.com')
            ->assertJsonPath('success', true);

        $response = $this->actingAs($user, 'api')
            ->json(
                'POST',
                $this->route,
                [
                    'domain' => 'mywikidomain-2.com',
                    'sitename' => 'merp',
                    'username' => 'AdminBoss',
                ]
            );
        $response->assertStatus(403)
            ->assertJsonPath('message', 'Too many wikis. Your new total of 2 would exceed the limit of 1 per user.');

        // retry when disabled
        Config::set('wbstack.wiki_max_per_user', false);

        $response = $this->actingAs($user, 'api')
            ->json(
                'POST',
                $this->route,
                [
                    'domain' => 'mywikidomain-2.com',
                    'sitename' => 'merp',
                    'username' => 'AdminBoss',
                ]
            );

        $response->assertStatus(200)
            ->assertJsonPath('data.domain', 'mywikidomain-2.com')
            ->assertJsonPath('success', true);
    }

    private function createSQLandQSDBs(): void {
        $manager = $this->app->make('db');
        $job = new ProvisionWikiDbJob;
        $job->handle($manager);

        QueryserviceNamespace::create([
            'namespace' => 'derp',
            'backend' => 'wdqs.svc',
        ]);
    }

    /**
     * @dataProvider createWikiHandlesRangeOfPostValuesProvider
     */
    public function testCreateWikiHandlesRangeOfPostValues($data, $expectedStatus): void {
        $this->createSQLandQSDBs();
        Queue::fake();
        $user = User::factory()->create(['verified' => true]);
        $response = $this->actingAs($user, 'api')
            ->json(
                'POST',
                $this->route,
                $data
            );
        $response->assertStatus($expectedStatus);
    }

    public static function createWikiHandlesRangeOfPostValuesProvider(): array {
        $noDomain = self::defaultData;
        unset($noDomain['domain']);
        $noSitename = self::defaultData;
        unset($noSitename['sitename']);
        $noUsername = self::defaultData;
        unset($noUsername['username']);
        $noprofile = self::defaultData;
        unset($noprofile['profile']);
        $profileWithOther = self::defaultData;
        $profileWithOther['profile'] = '{
                        "audience": "other",
                        "audience_other": "just my cat",
                        "temporality": "permanent",
                        "purpose": "data_hub"
                      }';
        $profileWithOtherStringMissing = self::defaultData;
        $profileWithOtherStringMissing['profile'] = '{
            "audience": "other",
            "temporality": "permanent",
            "purpose": "data_hub"
          }';
        $profileWithExtraneousOther = self::defaultData;
        $profileWithExtraneousOther['profile'] = '{
                        "audience_other": "just my cat",
                        "temporality": "permanent",
                        "purpose": "data_hub"
                      }';
        $profileWithAudienceBlank = self::defaultData;
        $profileWithAudienceBlank['profile'] = '{
                        "audience": "",
                        "temporality": "permanent",
                        "purpose": "data_hub"
                    }';

        return [
            'all params present' => [self::defaultData, 200],
            'missing domain' => [$noDomain, 422],
            'missing sitename' => [$noSitename, 422],
            'missing username' => [$noUsername, 422],
            'missing profile' => [$noprofile, 200],
            'profile with other' => [$profileWithOther, 200],
            'profile with other string missing' => [$profileWithOtherStringMissing, 422],
            'profile with extraneous other' => [$profileWithExtraneousOther, 422],
            'profile with audience blank string' => [$profileWithAudienceBlank, 422],
        ];
    }

    public function testCreateWithProfileCreatesProfiles(): void {
        $this->createSQLandQSDBs();
        Queue::fake();
        $user = User::factory()->create(['verified' => true]);
        $response = $this->actingAs($user, 'api')
            ->json(
                'POST',
                $this->route,
                self::defaultData
            );
        $id = $response->decodeResponseJson()['data']['id'];
        $this->assertEquals(1,
            WikiProfile::where(['wiki_id' => $id])->count()
        );
    }
}
