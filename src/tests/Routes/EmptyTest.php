<?php

namespace App\Tests\Routes;

use App\Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class EmptyTest extends TestCase
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
