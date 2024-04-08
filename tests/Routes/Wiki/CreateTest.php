<?php

namespace Tests\Routes\Wiki\Managers;

use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\User;
use Illuminate\Support\Facades\Queue;
use App\Jobs\CirrusSearch\ElasticSearchIndexInit;
use App\Jobs\ElasticSearchAliasInit;
use App\Jobs\ProvisionWikiDbJob;
use App\Jobs\MediawikiInit;
use App\WikiSetting;
use App\WikiManager;
use App\Wiki;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use App\QueryserviceNamespace;

class CreateTest extends TestCase
{
    protected $route = 'wiki/create';

    use OptionsRequestAllowed;
    use DatabaseTransactions;

    /**
	 * @dataProvider createProvider
	 */
    public function testWikiCreateDispatchesSomeJobs( $elasticSearchConfig )
    {
        $enabledForNewWikis = $elasticSearchConfig[ 'enabledForNewWikis' ];
        $clusterWithoutSharedIndex = $elasticSearchConfig[ 'clusterWithoutSharedIndex' ] ?? null;
        $sharedIndexHost = $elasticSearchConfig[ 'sharedIndexHost' ] ?? null;
        $sharedIndexPrefix = $elasticSearchConfig[ 'sharedIndexPrefix' ] ?? null;

        Config::set( 'wbstack.elasticsearch_enabled_by_default', $enabledForNewWikis );
        Config::set( 'wbstack.elasticsearch_cluster_without_shared_index', $clusterWithoutSharedIndex );
        Config::set( 'wbstack.elasticsearch_shared_index_host', $sharedIndexHost );
        Config::set( 'wbstack.elasticsearch_shared_index_prefix', $sharedIndexPrefix );

        // seed up ready db
        $manager = $this->app->make('db');
        $job = new ProvisionWikiDbJob();
        $job->handle($manager);

        $dbRow = QueryserviceNamespace::create([
            'namespace' => "derp",
            'backend' => "wdqs.svc",
        ]);

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
                'username' => 'AdminBoss'
            ]
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.domain', 'derp.com')
            ->assertJsonPath('data.name', null)
            ->assertJsonPath('success', true );

        Queue::assertPushed( ProvisionWikiDbJob::class, 1);
        Queue::assertPushed( MediawikiInit::class, 1);

        if ( $enabledForNewWikis && $clusterWithoutSharedIndex ) {
            Queue::assertPushed( function ( ElasticSearchIndexInit $job ) use ( $clusterWithoutSharedIndex ) {
                return $job->cluster() === $clusterWithoutSharedIndex;
            } );
        } else {
            Queue::assertNotPushed( ElasticSearchIndexInit::class );
        }

        if ( $enabledForNewWikis && $sharedIndexHost && $sharedIndexPrefix ) {
            Queue::assertPushed( ElasticSearchAliasInit::class, 1 );
        } else {
            Queue::assertNotPushed( ElasticSearchAliasInit::class );
        }

        $id = $response->original['data']['id'];

        $this->assertSame(
            1,
            WikiSetting::where( [ 'name' => WikiSetting::wgSecretKey, 'wiki_id' => $id ] )->count()
        );

        $this->assertSame(
            1,
            WikiSetting::where( [ 'name' => WikiSetting::wwExtEnableElasticSearch, 'value' => $enabledForNewWikis, 'wiki_id' => $id ] )->count()
        );
    }

    static public function createProvider() {
        yield [ [
            'enabledForNewWikis' => true,
            'clusterWithoutSharedIndex' => 'all',
            'sharedIndexHost' => 'somehost',
            'sharedIndexPrefix' => 'testing_1'
        ] ];

        yield [ [
            'enabledForNewWikis' => true,
            'clusterWithoutSharedIndex' => 'default',
        ] ];

        yield [ [
            'enabledForNewWikis' => true,
            'sharedIndexHost' => 'somehost',
            'sharedIndexPrefix' => 'testing_1'
        ] ];

        yield [ [
            'enabledForNewWikis' => true,
            'sharedIndexPrefix' => 'testing_1'
        ] ];

        yield [ [
            'enabledForNewWikis' => true
        ] ];

        yield [ [
            'enabledForNewWikis' => false
        ] ];
    }

    public function testCreateWikiLimitsNumWikisPerUser()
    {
        $manager = $this->app->make('db');

        $job1 = new ProvisionWikiDbJob();
        $job1->handle($manager);

        $job2 = new ProvisionWikiDbJob();
        $job2->handle($manager);

        QueryserviceNamespace::create([
            'namespace' => "ns-1",
            'backend' => "wdqs.svc",
        ]);
        QueryserviceNamespace::create([
            'namespace' => "ns-2",
            'backend' => "wdqs.svc",
        ]);

        Config::set('wbstack.wiki_max_per_user', 1);

        Queue::fake();

        $user = User::factory()->create(['verified' => true]);
        Queue::assertNothingPushed();

        // This shouldn't stop first create since it's deleted
        $this->wiki = Wiki::factory()->create( [ 'deleted_at' => Carbon::now()->timestamp ] );
        WikiManager::factory()->create(['wiki_id' => $this->wiki->id, 'user_id' => $user->id]);

        $response = $this->actingAs($user, 'api')
        ->json(
            'POST',
            $this->route,
            [
                'domain' => 'mywikidomain.com',
                'sitename' => 'merp',
                'username' => 'AdminBoss'
            ]
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.domain', 'mywikidomain.com')
            ->assertJsonPath('success', true );

        $response = $this->actingAs($user, 'api')
            ->json(
                'POST',
                $this->route,
                [
                    'domain' => 'mywikidomain-2.com',
                    'sitename' => 'merp',
                    'username' => 'AdminBoss'
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
                'username' => 'AdminBoss'
            ]
        );


        $response->assertStatus(200)
            ->assertJsonPath('data.domain', 'mywikidomain-2.com')
            ->assertJsonPath('success', true );
    }
}
