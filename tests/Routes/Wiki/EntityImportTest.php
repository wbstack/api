<?php

namespace Tests\Routes\Wiki\Managers;

use App\WikiEntityImportStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;
use App\Wiki;
use App\WikiEntityImport;
use App\User;
use App\WikiManager;
use App\Jobs\WikiEntityImportJob;

class EntityImportTest extends TestCase
{
    protected $route = 'wiki/entityImport';

    use OptionsRequestAllowed, RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        Wiki::query()->delete();
        WikiManager::query()->delete();
        WikiEntityImport::query()->delete();
    }

    public function tearDown(): void
    {
        Wiki::query()->delete();
        WikiManager::query()->delete();
        WikiEntityImport::query()->delete();
        parent::tearDown();
    }

    public function testNoCredentials()
    {
        $wiki = Wiki::factory()->create(['domain' => 'test.wikibase.cloud']);
        $user = User::factory()->create(['verified' => true]);
        $this->actingAs($user, 'api')
            ->json('GET', $this->route.'?wiki='.$wiki->id)
            ->assertStatus(403);
    }

    public function testEmpty()
    {
        $wiki = Wiki::factory()->create(['domain' => 'test.wikibase.cloud']);
        $user = User::factory()->create(['verified' => true]);
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);
        $this->actingAs($user, 'api')
            ->json('GET', $this->route.'?wiki='.$wiki->id)
            ->assertStatus(200)
            ->assertJsonFragment(['data' => []]);
    }

    public function testResults()
    {
        $wiki = Wiki::factory()->create(['domain' => 'test.wikibase.cloud']);
        $otherWiki = Wiki::factory()->create(['domain' => 'other.wikibase.cloud']);
        $user = User::factory()->create(['verified' => true]);
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        WikiEntityImport::factory()->create([
            'status' => WikiEntityImportStatus::Success,
            'started_at' => Carbon::now()->subMinutes(5),
            'finished_at' => Carbon::now()->subMinutes(4),
            'wiki_id' => $wiki->id,
        ]);

        WikiEntityImport::factory()->create([
            'status' => WikiEntityImportStatus::Pending,
            'started_at' => Carbon::now()->subMinutes(8),
            'finished_at' => Carbon::now()->subMinutes(6),
            'wiki_id' => $otherWiki->id,
        ]);

        $this->actingAs($user, 'api')
            ->json('GET', $this->route.'?wiki='.$wiki->id)
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', WikiEntityImportStatus::Success->value);
    }

    public function testCreateWhilePending()
    {
        Bus::fake();
        $wiki = Wiki::factory()->create(['domain' => 'test.wikibase.cloud']);
        $user = User::factory()->create(['verified' => true]);
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        WikiEntityImport::factory()->create([
            'status' => WikiEntityImportStatus::Pending,
            'started_at' => Carbon::now()->subMinutes(5),
            'wiki_id' => $wiki->id,
        ]);

        $this->actingAs($user, 'api')
            ->json('POST', $this->route.'?wiki='.$wiki->id, ['entity_ids' => 'P1', 'source_wiki_url' => 'https://source.wikibase.cloud'])
            ->assertStatus(400);

        $this->assertEquals(1, WikiEntityImport::count());
        Bus::assertNothingDispatched();
    }

    public function testCreateWhenSucceeded()
    {
        Bus::fake();
        $wiki = Wiki::factory()->create(['domain' => 'test.wikibase.cloud']);
        $user = User::factory()->create(['verified' => true]);
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        WikiEntityImport::factory()->create([
            'status' => WikiEntityImportStatus::Success,
            'started_at' => Carbon::now()->subMinutes(5),
            'wiki_id' => $wiki->id,
        ]);

        $this->actingAs($user, 'api')
            ->json('POST', $this->route.'?wiki='.$wiki->id, ['source_wiki_url' => 'https://source.wikibase.cloud', 'entity_ids' => 'P1'])
            ->assertStatus(400);

        $this->assertEquals(1, WikiEntityImport::count());
        Bus::assertNothingDispatched();
    }

    public function testCreateWhenEmpty()
    {
        Bus::fake();
        $wiki = Wiki::factory()->create(['domain' => 'test.wikibase.cloud']);
        $user = User::factory()->create(['verified' => true]);
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        $this->actingAs($user, 'api')
            ->json('POST', $this->route.'?wiki='.$wiki->id, ['source_wiki_url' => 'https://source.wikibase.cloud', 'entity_ids' => 'P1,P2'])
            ->assertStatus(200);

        $this->assertEquals(1, WikiEntityImport::count());
        Bus::assertDispatchedTimes(WikiEntityImportJob::class, 1);
    }

    public function testCreateValidation()
    {
        Bus::fake();
        $wiki = Wiki::factory()->create(['domain' => 'test.wikibase.cloud']);
        $user = User::factory()->create(['verified' => true]);
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        $this->actingAs($user, 'api')
            ->json('POST', $this->route.'?wiki='.$wiki->id, ['source_wiki_url' => 'https://source.wikibase.cloud', 'entity_ids' => 'P1,P2; echo "P4Wn3D!!",Q42'])
            ->assertStatus(422);

        $this->assertEquals(0, WikiEntityImport::count());
        Bus::assertDispatchedTimes(WikiEntityImportDummyJob::class, 0);
    }
    public function testCreateWhenFailed()
    {
        Bus::fake();
        $wiki = Wiki::factory()->create(['domain' => 'test.wikibase.cloud']);
        $user = User::factory()->create(['verified' => true]);
        WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        WikiEntityImport::factory()->create([
            'status' => WikiEntityImportStatus::Failed,
            'started_at' => Carbon::now()->subMinutes(5),
            'wiki_id' => $wiki->id,
        ]);

        $this->actingAs($user, 'api')
            ->json('POST', $this->route.'?wiki='.$wiki->id, ['source_wiki_url' => 'https://source.wikibase.cloud', 'entity_ids' => 'P1,P2'])
            ->assertStatus(200);

        $this->assertEquals(2, WikiEntityImport::count());
        Bus::assertDispatchedTimes(WikiEntityImportJob::class, 1);
    }
}
