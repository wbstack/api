<?php

namespace Tests\Routes\QsBatch;

use App\EventPageUpdate;
use App\QsBatch;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class QsControllerTest extends TestCase
{
    protected $route = 'backend/qs/getBatches';

    use DatabaseTransactions;

    public function setUp (): void
    {
        parent::setUp();
        EventPageUpdate::query()->delete();
        QsBatch::query()->delete();
    }

    public function tearDown (): void
    {
        EventPageUpdate::query()->delete();
        QsBatch::query()->delete();
        parent::tearDown();
    }

    public function testEmpty (): void
    {
        $this->json('GET', $this->route)
            ->assertJson([])
            ->assertStatus(200);
    }
}
