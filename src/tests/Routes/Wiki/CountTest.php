<?php

namespace App\Tests\Routes\Wiki\Managers;

use App\Wiki;
use App\Tests\TestCase;
use App\Tests\Routes\Traits\CrossSiteHeadersOnOptions;
use App\Tests\Routes\Traits\OptionsRequestAllowed;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

/**
 * @covers WikiController::count
 */
class CountTest extends TestCase
{

  protected $route = 'wiki/count';

  use CrossSiteHeadersOnOptions;
  use OptionsRequestAllowed;

    // DB needs to be empty before this
    use DatabaseMigrations;

    public function testRootPostNotAllowed()
    {
        $this->post($this->route);
        // Method not allowed
        $this->assertEquals(405, $this->response->status());
    }

    public function testWikiCountNone()
    {
        $this->get('/wiki/count')->seeJsonEquals([
          'data' => 0,
          'success' => true
        ]);
        $this->assertEquals(200, $this->response->status());
    }

    public function testWikiCountOne()
    {
        $wikiDb = Wiki::create([
            'sitename' => 'a',
            'domain' => 'a.com',
        ]);

        $this->seeInDatabase('wikis', [
          'domain' => 'a.com',
          'sitename' => 'a',
        ]);

        $this->get($this->route)->seeJsonEquals([
          'data' => 1,
          'success' => true
        ]);
        $this->assertEquals(200, $this->response->status());
    }

    public function testWikiCountTwo()
    {
        $wikiDb = Wiki::create([
            'sitename' => 'a',
            'domain' => 'a.com',
        ]);
        $wikiDb = Wiki::create([
            'sitename' => 'b',
            'domain' => 'b.com',
        ]);

        $this->seeInDatabase('wikis', [
          'domain' => 'a.com',
          'sitename' => 'a',
        ]);

        $this->seeInDatabase('wikis', [
          'domain' => 'b.com',
          'sitename' => 'b',
        ]);

        $this->get($this->route)->seeJsonEquals([
          'data' => 2,
          'success' => true
        ]);
        $this->assertEquals(200, $this->response->status());
    }
}
