<?php

namespace Tests\Routes\Wiki\Managers;

use App\Wiki;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;

/**
 * @covers WikiController::count
 */
class CountTest extends TestCase
{
    protected $route = 'wiki/count';

    use OptionsRequestAllowed;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Wiki::all()->each(function ($a) {
            $a->destroy($a->id);
        });
    }

    public function testWikiCountNone()
    {
        $this->json('GET', '/wiki/count')->assertJson([
          'data' => 0,
          'success' => true,
        ])
        ->assertStatus(200);
    }

    public function testWikiCountOne()
    {
        // TODO should wikis be counted if they have no db?
        // TODO what actually is the use of this whole count??
        factory(Wiki::class, 'nodb')->create();
        $this->json('GET', $this->route)->assertJson([
          'data' => 1,
          'success' => true,
        ])
        ->assertStatus(200);
    }

    public function testWikiCountTwo()
    {
        factory(Wiki::class, 'nodb')->create();
        factory(Wiki::class, 'nodb')->create();
        $this->json('GET', $this->route)->assertJson([
          'data' => 2,
          'success' => true,
        ])
        ->assertStatus(200);
    }
}
