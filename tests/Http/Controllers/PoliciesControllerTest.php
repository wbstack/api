<?php

namespace Tests\Http\Controllers;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PoliciesControllerTest extends TestCase {
    use DatabaseTransactions;

    public function testGetCurrentPolicies(): void {
        //$policy = Policy::factory()->create();
    }
}
