<?php

namespace Tests\Http\Controllers;

use App\Policy;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PoliciesControllerTest extends TestCase {
    use DatabaseTransactions;

    public function testGetCurrentPolicies(): void {
        // Future policy
        Policy::factory()->create([
            'active_from' => now()->addDay(),
        ]);
        // Active policy
        Policy::factory()->create();
        // Active policy
        Policy::factory()->create([
            'active_from' => now()->subMonth(),
        ]);

        $response = $this->getJson('/policies/current');

        $response->assertOk();
        $response->assertJsonCount(2, 'data.items');
    }
}
