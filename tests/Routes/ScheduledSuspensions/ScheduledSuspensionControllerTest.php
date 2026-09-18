<?php

namespace Tests\Routes\ScheduledSuspensions;

use App\ScheduledSuspension;
use App\Wiki;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\TModel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ScheduledSuspensionControllerTest extends TestCase {
    protected $baseRoute = 'v1/scheduledSuspension';

    private Wiki $wiki;

    use DatabaseTransactions;

    protected function setUp(): void {
        parent::setUp();
        $this->wiki = Wiki::factory()->create();
    }

    protected function tearDown(): void {
        parent::tearDown();
    }

    public function testGetEmpty(): void {
        $this->json('GET', $this->baseRoute )
        ->assertExactJson([])
        ->assertStatus(200);
    }

    public function testGetIndexWithContent(): void {
        ScheduledSuspension::create([
                'active_from' => '2028-01-01',
                'wiki_id' => $this->wiki->id
            ]);
        $this->json('GET', $this->baseRoute )
        ->assertJsonCount(1)
        ->assertStatus(200);
    }

    public function testGetAdminCreateSuspension(): void {
        $this->json(
            'POST',
            $this->baseRoute,
            $data = [
                'active_from' => '2028-01-01',
                'wiki_id' => $this->wiki->id
            ]);
        $this->assertModelExists(ScheduledSuspension::first());
    }

    public function testGetSuspensionById(): void {
        $suspension = ScheduledSuspension::create([
            'active_from' => '2029-01-01',
            'wiki_id' => $this->wiki->id
        ]);
        $this->json('GET', $this->baseRoute . '/' . $suspension->id )
        ->assertStatus(200)
        ->assertJsonFragment(['active_from' => '2029-01-01']);
    }

    public function testGetAdminUpdateSuspension(): void {
        $suspension = ScheduledSuspension::create([
            'active_from' => '2029-01-01',
            'wiki_id' => $this->wiki->id
        ]);
        $this->json('PUT', $this->baseRoute . '/' . $suspension->id, $data = ['active_from' => '2028-01-01'] )
        ->assertStatus(200);
        $suspension->refresh();
        $this->assertEquals( '2028-01-01', $suspension->active_from );

    }

    public function testGetAdminRemoveSuspension(): void {
        $suspension = ScheduledSuspension::create([
            'active_from' => '2029-01-01',
            'wiki_id' => $this->wiki->id
        ]);
        $this->json('DELETE', $this->baseRoute . '/' . $suspension->id )
        ->assertStatus(200);
        $this->assertModelMissing($suspension);
    }

    public function testGetActiveSuspensionsByWiki(): void {
        $suspension = ScheduledSuspension::create([
            'active_from' => '2029-01-01',
            'wiki_id' => $this->wiki->id
        ]);
        $response = $this->json('GET', $this->baseRoute . '/wiki/' . $this->wiki->id );
        $response
        ->assertStatus(200)
        ->assertJsonFragment(['active_from' => '2029-01-01']);
    }


}
