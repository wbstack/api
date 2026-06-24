<?php

namespace Tests;

use App\Policy;
use App\PolicyAcceptance;
use App\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PolicyAcceptanceTest extends TestCase {
    use RefreshDatabase;

    protected int $user_id;

    protected int $policy_id;

    protected function setUp(): void {
        parent::setUp();
        $user = User::factory()->create();
        $this->user_id = $user->id;
        $policy = Policy::create(
            [
                'policy_type' => 'terms-of-use',
                'active_from' => CarbonImmutable::yesterday(),
                'content_vue_file' => 'terms-of-use/example.vue',
            ]);
        $policy->save();
        $this->policy_id = $policy->id;
    }

    public function testCreatesAndSavesSuccessfully(): void {
        $policyAcceptance = new PolicyAcceptance(
            [
                'user_id' => $this->user_id,
                'policy_id' => $this->policy_id,
            ]
        );
        $policyAcceptance->save();
        $policyAcceptance->refresh();

        $this->assertDatabaseHas('policy_acceptances', [
            'user_id' => $this->user_id,
            'policy_id' => $this->policy_id,
        ]);

        $this->assertNotEmpty($policyAcceptance->accepted_at);
    }
}
