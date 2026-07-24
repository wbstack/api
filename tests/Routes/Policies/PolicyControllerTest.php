<?php

namespace Http\Controllers;

use App\Policy;
use Carbon\CarbonImmutable;
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
        $request->assertJsonStructure([
            'metadata' => [
                'policy_id',
                'active_from',
                'content_vue_file',
                'type',
            ],
        ]);
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

    public function testUpcomingTermsOfUseMissing(): void {
        Policy::query()->delete();

        $request = $this->getJson('v1/policies/terms-of-use/upcoming');
        $request->assertNotFound();
    }

    public function testUpcomingHostingPolicyMissing(): void {
        Policy::query()->delete();

        $request = $this->getJson('v1/policies/hosting-policy/upcoming');
        $request->assertNotFound();
    }

    public function testUpcomingTermsOfUseMultiple(): void {
        Policy::query()->delete();

        $now = CarbonImmutable::now();

        Policy::factory()->create([
            'policy_type' => 'terms-of-use',
            'active_from' => $now->addWeek(),
        ]);

        Policy::factory()->create([
            'policy_type' => 'terms-of-use',
            'active_from' => null,
        ]);

        $request = $this->getJson('v1/policies/terms-of-use/upcoming');
        $request->assertServerError();
    }

    public function testUpcomingTermsOfUse(): void {
        Policy::query()->delete();

        $now = CarbonImmutable::now();

        Policy::factory()->create([
            'policy_type' => 'terms-of-use',
            'active_from' => $now->addWeek(),
            'content_vue_file' => 'terms-of-use/version-1.vue',
        ]);

        $request = $this->getJson('v1/policies/terms-of-use/upcoming');

        $request->assertOk();
        $request->assertJsonStructure([
            'metadata' => [
                'policy_id',
                'active_from',
                'content_vue_file',
                'type',
            ],
        ]);

        $request->assertJsonFragment([
            'active_from' => $now->addWeek()->format('Y-m-d'),
            'type' => 'terms-of-use',
            'content_vue_file' => 'terms-of-use/version-1.vue',
        ]);
    }

    public function testCurrentTermsOfUseMissing(): void {
        Policy::query()->delete();

        $request = $this->getJson('v1/policies/terms-of-use/current');
        $request->assertNotFound();
    }

    public function testCurrentHostingPolicyMissing(): void {
        Policy::query()->delete();

        $request = $this->getJson('v1/policies/hosting-policy/current');
        $request->assertNotFound();
    }

    public function testCurrentTermsOfUse() {
        $now = CarbonImmutable::now();

        Policy::factory()->create([
            'policy_type' => 'terms-of-use',
            'active_from' => $now->subMonth(),
            'content_vue_file' => 'terms-of-use/version-1.vue',
        ]);

        $current = Policy::factory()->create([
            'policy_type' => 'terms-of-use',
            'active_from' => $now->subWeek(),
            'content_vue_file' => 'terms-of-use/version-2.vue',
        ]);

        $request = $this->getJson('v1/policies/terms-of-use/current');

        $request->assertOk();
        $request->assertJsonStructure([
            'metadata' => [
                'policy_id',
                'active_from',
                'content_vue_file',
                'type',
            ],
        ]);

        $request->assertJsonFragment([
            'active_from' => $current->active_from->format('Y-m-d'),
            'type' => $current->policy_type,
            'content_vue_file' => $current->content_vue_file,
        ]);
    }
}
