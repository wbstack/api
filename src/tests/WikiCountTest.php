<?php

use App\Wiki;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

/**
 * @covers WikiController::count
 */
class WikiCountTest extends TestCase
{
    use DatabaseTransactions;

    public function testRootPostNotAllowed()
    {
        $this->post('/wiki/count');
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

        $this->get('/wiki/count')->seeJsonEquals([
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

        $this->get('/wiki/count')->seeJsonEquals([
          'data' => 2,
          'success' => true
        ]);
        $this->assertEquals(200, $this->response->status());
    }
}
