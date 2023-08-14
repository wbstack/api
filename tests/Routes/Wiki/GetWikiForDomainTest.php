<?php

namespace Tests\Routes\Wiki;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateTest extends TestCase
{
    protected $route = '/backend/wiki/getWikiForDomain';

    use RefreshDatabase;

    public function testNotFound()
    {
        $response = $this->json('GET', $this->route."?domain=notfound.wikibase.cloud");
        $response->assertStatus(404);
    }
}
