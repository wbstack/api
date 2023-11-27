<?php

namespace Tests\Routes\QsBatch;

use App\EventPageUpdate;
use App\QsBatch;
use App\Wiki;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class QsControllerTest extends TestCase
{
    protected $route = 'backend/qs';

    use DatabaseTransactions;

    public function setUp (): void
    {
        parent::setUp();
        EventPageUpdate::query()->delete();
        QsBatch::query()->delete();
        Wiki::query()->delete();
    }

    public function tearDown (): void
    {
        EventPageUpdate::query()->delete();
        QsBatch::query()->delete();
        Wiki::query()->delete();
        parent::tearDown();
    }

    public function testGetEmpty (): void
    {
        $this->json('GET', $this->route.'/getBatches')
            ->assertJson([])
            ->assertStatus(200);
        }

    public function testGetOldestBatch (): void
    {
        Wiki::factory()->create(['id' => 99, 'domain' => 'test.wikibase.cloud']);
        QsBatch::factory()->create(['id' => 1, 'done' => 0, 'failed' => true, 'wiki_id' => 99, 'entityIds' => 'a,b']);
        QsBatch::factory()->create(['id' => 2, 'done' => 1, 'wiki_id' => 99, 'entityIds' => 'a,b']);
        QsBatch::factory()->create(['pending_since' => Carbon::now()->subMinutes(4), 'id' => 3, 'done' => 0, 'wiki_id' => 99, 'entityIds' => 'a,b']);
        QsBatch::factory()->create(['id' => 4, 'done' => 0, 'wiki_id' => 99, 'entityIds' => 'a,b']);
        QsBatch::factory()->create(['id' => 5, 'done' => 0, 'wiki_id' => 99, 'entityIds' => 'a,b']);

        $response = $this->json('GET', $this->route.'/getBatches')
            ->assertJsonPath('0.id', 4)
            ->assertJsonPath('0.done', 0)
            ->assertJsonPath('0.wiki.domain', 'test.wikibase.cloud')
            ->assertStatus(200);

        $this->assertNotNull($response->json()[0]['pending_since']);
    }

    public function testMarkDone (): void {
        QsBatch::factory()->create(['pending_since' => Carbon::now()->subSeconds(1), 'id' => 1, 'done' => 1, 'wiki_id' => 1, 'entityIds' => 'a,b']);
        QsBatch::factory()->create(['pending_since' => Carbon::now()->subSeconds(2), 'id' => 2, 'done' => 0, 'wiki_id' => 1, 'entityIds' => 'c,d']);
        QsBatch::factory()->create(['pending_since' => Carbon::now()->subSeconds(3), 'id' => 3, 'done' => 0, 'wiki_id' => 1, 'entityIds' => 'e,f']);
        QsBatch::factory()->create(['pending_since' => Carbon::now()->subSeconds(4), 'id' => 4, 'done' => 0, 'wiki_id' => 6, 'entityIds' => 'g,h']);

        $this->json('POST', $this->route.'/markDone', ['batches' => [2, 3]])
            ->assertStatus(200);

        $this->assertEquals(QsBatch::where('id', 1)->first()->done, 1);
        $this->assertNotNull(QsBatch::where('id', 1)->first()->pending_since);
        $this->assertEquals(QsBatch::where('id', 2)->first()->done, 1);
        $this->assertNull(QsBatch::where('id', 2)->first()->pending_since);
        $this->assertEquals(QsBatch::where('id', 3)->first()->done, 1);
        $this->assertNull(QsBatch::where('id', 3)->first()->pending_since);
        $this->assertEquals(QsBatch::where('id', 4)->first()->done, 0);
        $this->assertNotNull(QsBatch::where('id', 4)->first()->pending_since);
    }
    public function testMarkNotDone (): void {
        QsBatch::factory()->create(['pending_since' => Carbon::now()->subSeconds(1), 'id' => 1, 'done' => 1, 'wiki_id' => 1, 'entityIds' => 'a,b']);
        QsBatch::factory()->create(['pending_since' => Carbon::now()->subSeconds(2), 'id' => 2, 'done' => 0, 'wiki_id' => 1, 'entityIds' => 'c,d']);
        QsBatch::factory()->create(['pending_since' => Carbon::now()->subSeconds(3), 'id' => 3, 'done' => 0, 'wiki_id' => 1, 'entityIds' => 'e,f']);
        QsBatch::factory()->create(['pending_since' => Carbon::now()->subSeconds(4), 'id' => 4, 'done' => 1, 'wiki_id' => 6, 'entityIds' => 'g,h']);

        $this->json('POST', $this->route.'/markNotDone', ['batches' => [1, 2]])
            ->assertStatus(200);

        $this->assertEquals(QsBatch::where('id', 1)->first()->done, 0);
        $this->assertNull(QsBatch::where('id', 1)->first()->pending_since);
        $this->assertEquals(QsBatch::where('id', 2)->first()->done, 0);
        $this->assertNull(QsBatch::where('id', 2)->first()->pending_since);
        $this->assertEquals(QsBatch::where('id', 3)->first()->done, 0);
        $this->assertNotNull(QsBatch::where('id', 3)->first()->pending_since);
        $this->assertEquals(QsBatch::where('id', 4)->first()->done, 1);
        $this->assertNotNull(QsBatch::where('id', 4)->first()->pending_since);
    }
}
