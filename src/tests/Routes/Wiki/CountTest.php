<?php

namespace App\Tests\Routes\Wiki\Managers;

use App\Wiki;
use App\Tests\TestCase;
use App\Tests\Routes\Traits\CrossSiteHeadersOnOptions;
use App\Tests\Routes\Traits\OptionsRequestAllowed;
use Laravel\Lumen\Testing\DatabaseTransactions;

/**
 * @covers WikiController::count
 */
class CountTest extends TestCase
{

  protected $route = 'wiki/count';

  use CrossSiteHeadersOnOptions;
  use OptionsRequestAllowed;

    use DatabaseTransactions;

    protected function setUp(): void {
      parent::setUp();
      Wiki::all()->each(function($a){$a->destroy($a->id);});
    }

    public function testWikiCountNone()
    {
        $this->get('/wiki/count')->seeJsonEquals([
          'data' => 0,
          'success' => true
        ])
        ->seeStatusCode(200);
    }

    public function testWikiCountOne()
    {
        // TODO should wikis be counted if they have no db?
        // TODO what actually is the use of this whole count??
        factory(Wiki::class, 'nodb')->create();
        $this->get($this->route)->seeJsonEquals([
          'data' => 1,
          'success' => true
        ])
        ->seeStatusCode(200);
    }

    public function testWikiCountTwo()
    {
        factory(Wiki::class, 'nodb')->create();
        factory(Wiki::class, 'nodb')->create();
        $this->get($this->route)->seeJsonEquals([
          'data' => 2,
          'success' => true
        ])
        ->seeStatusCode(200);
    }
}
