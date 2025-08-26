<?php

namespace Tests\Routes;

use Tests\TestCase;

class EmptyTest extends TestCase {
    public function testRootGetNotFound() {
        $response = $this->get('/');
        // Method not allowed
        $this->assertEquals(404, $response->status());
    }

    public function testRootPostNotFound() {
        $response = $this->post('/');
        // Method not allowed
        $this->assertEquals(404, $response->status());
    }
}
