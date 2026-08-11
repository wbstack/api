<?php

namespace Tests\Routes;

use App\ActiveSuspension;
use App\Wiki;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ActiveSuspensionControllerTest extends TestCase {
    protected $baseRoute = 'v1/activeSuspension';

    private Wiki $wiki;

    use DatabaseTransactions;

    protected function setUp(): void {
        parent::setUp();
        $this->wiki = Wiki::factory()->create();
    }

    protected function tearDown(): void {
        parent::tearDown();
    }

    public function testGetAdminCreateSuspension(): void {
        $this->json(
            'POST',
            $this->baseRoute,
            $data = [
                'since' => '2028-01-01',
                'wiki_id' => $this->wiki->id
            ]);

        $expectedSuspension = ActiveSuspension::where(
            [
                'since' => '2028-01-01',
                'wiki_id' => $this->wiki->id
            ]
        );
        $this->assertTrue($expectedSuspension->exists());
    }

    public function testGetAdminRemoveSuspension(): void {
        $suspension = ActiveSuspension::create([
            'since' => '2029-01-01',
            'wiki_id' => $this->wiki->id
        ]);
        $suspension2 = ActiveSuspension::create([
            'since' => '2029-02-01',
            'wiki_id' => $this->wiki->id
        ]);
        $suspension3 = ActiveSuspension::create([
            'since' => '2029-03-01',
            'wiki_id' => $this->wiki->id
        ]);
        $this->json('DELETE', $this->baseRoute . '/' . $suspension->id )
        ->assertStatus(200);
        $this->assertModelMissing($suspension);
    }

}
