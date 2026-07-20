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
        $response->assertJsonStructure([
            'items' => [
                '*' => [
                    'metadata' => [
                        'policy_id',
                        'active_from',
                        'content_vue_file',
                        'type',
                    ],
                ],
            ],
        ]);

        $response->assertJsonFragment([
            'policy_id' => $latestActiveToUPolicy->id,
            'active_from' => $latestActiveToUPolicy->active_from->format('Y-m-d'),
        ]);
        $response->assertJsonFragment([
            'policy_id' => $latestActiveHostingPolicy->id,
            'active_from' => $latestActiveHostingPolicy->active_from->format('Y-m-d'),
        ]);
    }

    public function testGetPolicyByTypeAndActiveFrom(): void {
        Policy::factory()->create([
            'policy_type' => 'hosting-policy',
            'active_from' => '2026-07-01',
        ]);

        Policy::factory()->create([
            'policy_type' => 'hosting-policy',
            'active_from' => '2026-07-02',
        ]);

        $request = $this->getJson('v1/policies/hosting-policy/by_active_from/2026-07-01');

        $request->assertOk();
        $request->assertJsonFragment([
            'active_from' => '2026-07-01',
            'type' => 'hosting-policy',
        ]);
    }

    public function testGetPolicyByTypeAndActiveFromReturns422WithInvalidParams(): void {
        $request = $this->getJson('v1/policies/fake-policy/by_active_from/not-a-date');
        $request->assertUnprocessable();
    }

    public function testMissingPolicyByTypeAndActiveFromReturns404(): void {
        $request = $this->getJson('v1/policies/hosting-policy/by_active_from/2026-07-01');
        $request->assertNotFound();
    }
}
