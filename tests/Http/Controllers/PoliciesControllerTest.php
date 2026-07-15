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
        $latestActiveToUPolicy = Policy::factory()->create([
            'policy_type' => 'terms-of-use',
            'active_from' => $currentTime->subMonth(),
        ]);
        $latestActiveHostingPolicy = Policy::factory()->create([
            'policy_type' => 'hosting-policy',
            'active_from' => $currentTime->subWeek(),
        ]);

        $response = $this->getJson('/v1/policies/current');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment([
            'id' => $latestActiveToUPolicy->id,
            'active_from' => $latestActiveToUPolicy->active_from,
        ]);
        $response->assertJsonFragment([
            'id' => $latestActiveHostingPolicy->id,
            'active_from' => $latestActiveHostingPolicy->active_from,
        ]);
    }
}
