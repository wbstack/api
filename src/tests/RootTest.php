<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class RootTest extends TestCase
{
    public function testRootGetNotAllowed()
    {
        $this->get('/');
        // Method not allowed
        $this->assertEquals(405, $this->response->status());
    }
    public function testRootPostNotAllowed()
    {
        $this->post('/');
        // Method not allowed
        $this->assertEquals(405, $this->response->status());
    }
}
