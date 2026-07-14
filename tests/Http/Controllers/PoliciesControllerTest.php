<?php

namespace Tests\Http\Controllers;

use App\Policy;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PoliciesControllerTest extends TestCase {
    use DatabaseTransactions;

    public function testGetCurrentPolicies(): void {
        $currentTime = now();
        // Future policy
        Policy::factory()->create([
            'policy_type' => 'terms-of-use',
            'active_from' => $currentTime->addDay(),
        ]);
        // Old policy
        Policy::factory()->create([
            'policy_type' => 'hosting-policy',
            'active_from' => $currentTime->subMonth(),
        ]);
        // Active policies
        $latestToUPolicy = Policy::factory()->create([
            'policy_type' => 'terms-of-use',
            'active_from' => $currentTime->subMonth(),
        ]);
        $latestHostingPolicy = Policy::factory()->create([
            'policy_type' => 'hosting-policy',
            'active_from' => $currentTime->subWeek(),
        ]);

        $response = $this->getJson('/policies/current');

        $response->assertOk();
        $response->assertJsonCount(2, 'data.items');
        $response->assertJsonFragment([
            'id' => $latestToUPolicy->id,
            'active_from' => $latestToUPolicy->active_from,
        ]);
        $response->assertJsonFragment([
            'id' => $latestHostingPolicy->id,
            'active_from' => $latestHostingPolicy->active_from,
        ]);
    }
}
