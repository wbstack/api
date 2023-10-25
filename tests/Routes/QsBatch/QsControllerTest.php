<?php

namespace Tests\Routes\QsBatch;

use App\EventPageUpdate;
use App\QsBatch;
use App\Wiki;
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
        QsBatch::factory()->create(['id' => 1, 'done' => 1, 'eventFrom' => 1, 'eventTo' => 2, 'wiki_id' => 99, 'entityIds' => 'a,b']);
        QsBatch::factory()->create(['id' => 2, 'done' => 0, 'eventFrom' => 1, 'eventTo' => 2, 'wiki_id' => 99, 'entityIds' => 'a,b']);
        QsBatch::factory()->create(['id' => 3, 'done' => 0, 'eventFrom' => 1, 'eventTo' => 2, 'wiki_id' => 99, 'entityIds' => 'a,b']);
        $this->json('GET', $this->route.'/getBatches')
            ->assertJsonPath('0.id', 2)
            ->assertJsonPath('0.done', 1)
            ->assertJsonPath('0.wiki.domain', 'test.wikibase.cloud')
            ->assertStatus(200);
    }

    public function testImplicitBatchCreation (): void
    {
        Wiki::factory()->create(['id' => 99, 'domain' => 'test.wikibase.cloud']);
        QsBatch::factory()->create(['id' => 1, 'done' => 0, 'eventFrom' => 1, 'eventTo' => 2, 'wiki_id' => 99, 'entityIds' => 'a,b']);
        QsBatch::factory()->create(['id' => 2, 'done' => 0, 'eventFrom' => 0, 'eventTo' => 0, 'wiki_id' => 99, 'entityIds' => 'a,b']);
        EventPageUpdate::factory()->create(['wiki_id' => 111, 'namespace' => 120, 'title' => 'name']);

        $this->json('GET', $this->route.'/getBatches')
            ->assertJsonPath('0.id', 1)
            ->assertJsonPath('0.done', 1)
            ->assertJsonPath('0.wiki.domain', 'test.wikibase.cloud')
            ->assertStatus(200);

        $this->assertEquals(QsBatch::query()->count(), 3);
    }

    public function testMarkDone (): void {
        QsBatch::factory()->create(['id' => 1, 'done' => 1, 'eventFrom' => 1, 'eventTo' => 2, 'wiki_id' => 1, 'entityIds' => 'a,b']);
        QsBatch::factory()->create(['id' => 2, 'done' => 0, 'eventFrom' => 2, 'eventTo' => 3, 'wiki_id' => 1, 'entityIds' => 'c,d']);
        QsBatch::factory()->create(['id' => 3, 'done' => 0, 'eventFrom' => 3, 'eventTo' => 4, 'wiki_id' => 1, 'entityIds' => 'e,f']);
        QsBatch::factory()->create(['id' => 4, 'done' => 0, 'eventFrom' => 4, 'eventTo' => 5, 'wiki_id' => 6, 'entityIds' => 'g,h']);

        $this->json('POST', $this->route.'/markDone', ['batches' => '2,3'])
            ->assertStatus(200);

        $this->assertEquals(QsBatch::where('id', 1)->first()->done, 1);
        $this->assertEquals(QsBatch::where('id', 2)->first()->done, 1);
        $this->assertEquals(QsBatch::where('id', 3)->first()->done, 1);
        $this->assertEquals(QsBatch::where('id', 4)->first()->done, 0);
    }
    public function testMarkFailed (): void {
        QsBatch::factory()->create(['id' => 1, 'done' => 1, 'eventFrom' => 1, 'eventTo' => 2, 'wiki_id' => 1, 'entityIds' => 'a,b']);
        QsBatch::factory()->create(['id' => 2, 'done' => 0, 'eventFrom' => 2, 'eventTo' => 3, 'wiki_id' => 1, 'entityIds' => 'c,d']);
        QsBatch::factory()->create(['id' => 3, 'done' => 0, 'eventFrom' => 3, 'eventTo' => 4, 'wiki_id' => 1, 'entityIds' => 'e,f']);
        QsBatch::factory()->create(['id' => 4, 'done' => 1, 'eventFrom' => 4, 'eventTo' => 5, 'wiki_id' => 6, 'entityIds' => 'g,h']);

        $this->json('POST', $this->route.'/markFailed', ['batches' => '1,2'])
            ->assertStatus(200);

        $this->assertEquals(QsBatch::where('id', 1)->first()->done, 0);
        $this->assertEquals(QsBatch::where('id', 2)->first()->done, 0);
        $this->assertEquals(QsBatch::where('id', 3)->first()->done, 0);
        $this->assertEquals(QsBatch::where('id', 4)->first()->done, 1);
    }
}
