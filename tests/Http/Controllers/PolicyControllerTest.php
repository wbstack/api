<?php

namespace Http\Controllers;

use App\Policy;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PolicyControllerTest extends TestCase {
    use DatabaseTransactions;

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
