<?php

namespace Tests\Feature;

use App\User;
use App\Wiki;
use App\WikiManager;
use App\WikiSetting;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ElasticSearchIndexInit;
use Carbon\Carbon;

class ElasticSearchWikiSettingsObserverTest extends TestCase
{
    use DatabaseTransactions;

    public function testGivenElasticSearchEnabled_createLexemeSetting()
    {
        Queue::fake();

        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create();
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        Queue::assertNothingPushed();

        WikiSetting::factory()->create(
            [
                'wiki_id' => $wiki->id,
                'name' => WikiSetting::wwExtEnableElasticSearch,
                'value' => '1'
            ]
        );

        Queue::assertPushed(ElasticSearchIndexInit::class, function ($job) use ( $wiki ) {
            return $job->uniqueId() === $wiki->domain;
        });

        Queue::assertPushed(ElasticSearchIndexInit::class, 1);

        WikiSetting::factory()->create(
            [
                'wiki_id' => $wiki->id,
                'name' => WikiSetting::wwExtEnableWikibaseLexeme,
                'value' => '1',
            ]
        );

        // lexeme requires an update too
        Queue::assertPushed(ElasticSearchIndexInit::class, 2);
    }

    public function testGivenElasticSearchDisabled_createLexemeSetting()
    {
        Queue::fake();

        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create();
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        Queue::assertNothingPushed();

        WikiSetting::factory()->create(
            [
                'wiki_id' => $wiki->id,
                'name' => WikiSetting::wwExtEnableElasticSearch,
                'value' => '0'
            ]
        );

        Queue::assertNothingPushed();

        WikiSetting::factory()->create(
            [
                'wiki_id' => $wiki->id,
                'name' => WikiSetting::wwExtEnableWikibaseLexeme,
                'value' => '1',
            ]
        );

        Queue::assertNotPushed(ElasticSearchIndexInit::class);
    }

    public function testGivenLexemeEnabled_enableElasticSearchTriggersIndex()
    {
        Queue::fake();

        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create();
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        Queue::assertNothingPushed();

        WikiSetting::factory()->create(
            [
                'wiki_id' => $wiki->id,
                'name' => WikiSetting::wwExtEnableWikibaseLexeme,
                'value' => '1'
            ]
        );

        $elasticSearch = WikiSetting::factory()->create(
            [
                'wiki_id' => $wiki->id,
                'name' => WikiSetting::wwExtEnableElasticSearch,
                'value' => '0',
            ]
        );

        Queue::assertNothingPushed();

        WikiSetting::whereId( $elasticSearch->id )->first()->update(
            [
                'value' => '1',
            ]
        );

        Queue::assertPushed(ElasticSearchIndexInit::class, 1);
            
        Queue::assertPushed(ElasticSearchIndexInit::class, function ($job) use ( $wiki ) {
            return $job->uniqueId() === $wiki->domain;
        });
    }

    public function testDoesNotDispatchOnBulkUpdates()
    {
        Queue::fake();

        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create();
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        WikiSetting::factory()->create(
            [
                'wiki_id' => $wiki->id,
                'name' => WikiSetting::wwExtEnableWikibaseLexeme,
                'value' => '1'
            ]
        );

        $elasticSearch = WikiSetting::factory()->create(
            [
                'wiki_id' => $wiki->id,
                'name' => WikiSetting::wwExtEnableElasticSearch,
                'value' => '0',
            ]
        );

        Queue::assertNothingPushed();

        // bulk update shouldn't trigger
        WikiSetting::whereId( $elasticSearch->id )->update(
            [
                'value' => '1',
            ]
        );
            
        Queue::assertNothingPushed();

        $this->assertSame(
            2,
            WikiSetting::where(
                [ 
                    'wiki_id' => $elasticSearch->wiki_id,
                    'value' => '1'
                ]
            )->count()
        );
    }

    public function testDoesNotDispatchForDeletedWikis()
    {
        Queue::fake();

        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create( ['deleted_at' => Carbon::now()->toDateTimeString()] );
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        WikiSetting::factory()->create(
            [
                'wiki_id' => $wiki->id,
                'name' => WikiSetting::wwExtEnableWikibaseLexeme,
                'value' => '1'
            ]
        );

        $elasticSearch = WikiSetting::factory()->create(
            [
                'wiki_id' => $wiki->id,
                'name' => WikiSetting::wwExtEnableElasticSearch,
                'value' => '1',
            ]
        );
    
        $this->assertSame(
            2,
            WikiSetting::where(
                [ 
                    'wiki_id' => $elasticSearch->wiki_id,
                    'value' => '1'
                ]
            )->count()
        );
            
        Queue::assertNothingPushed();
    }


    public function testCreatesIndexByDefault()
    {
        Queue::fake();

        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create();
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        WikiSetting::factory()->create(
            [
                'wiki_id' => $wiki->id,
                'name' => WikiSetting::wwExtEnableElasticSearch,
                'value' => '1'
            ]
        );

        Queue::assertPushed(ElasticSearchIndexInit::class, function ($job) use ( $wiki ) {
            return $job->uniqueId() === $wiki->domain;
        });
    }
}
