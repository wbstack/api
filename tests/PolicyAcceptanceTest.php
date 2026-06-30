<?php

namespace Tests;

use App\Policy;
use App\PolicyAcceptance;
use App\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;

class PolicyAcceptanceTest extends TestCase {
    use RefreshDatabase;

    protected int $userId;

    protected int $policyId;

    protected function setUp(): void {
        parent::setUp();
        $user = User::factory()->create();
        $this->userId = $user->id;
        $policy = Policy::create(
            [
                'policy_type' => 'terms-of-use',
                'active_from' => CarbonImmutable::yesterday(),
                'content_vue_file' => 'terms-of-use/example.vue',
            ]);
        $this->policyId = $policy->id;
    }

    public function testCreatesAndSavesSuccessfully(): void {
        $policyAcceptance = new PolicyAcceptance(
            [
                'user_id' => $this->userId,
                'policy_id' => $this->policyId,
                'accepted_at' => CarbonImmutable::now(),
            ]
        );
        $policyAcceptance->save();
        $policyAcceptance->refresh();

        $this->assertDatabaseHas('policy_acceptances', [
            'user_id' => $this->userId,
            'policy_id' => $this->policyId,
        ]);

        $this->assertNotEmpty($policyAcceptance->accepted_at);
        $this->assertInstanceOf(CarbonImmutable::class, $policyAcceptance->accepted_at);
    }

    public function testCreateFailsIfAcceptedAtIsMissing() {
        $this->expectException(RuntimeException::class);
        PolicyAcceptance::create([
            'user_id' => $this->userId,
            'policy_id' => $this->policyId,
        ]);
    }

    public function testCreateFailsIfAcceptedAtIsNull() {
        $this->expectException(RuntimeException::class);
        PolicyAcceptance::create([
            'user_id' => $this->userId,
            'policy_id' => $this->policyId,
            'accepted_at' => null,
        ]);
    }

    public function testUserAcceptingSamePolicyTwiceFails() {
        PolicyAcceptance::create([
            'user_id' => $this->userId,
            'policy_id' => $this->policyId,
            'accepted_at' => CarbonImmutable::now()->subSeconds(2),
        ]);
        $this->expectException(RuntimeException::class);
        PolicyAcceptance::create([
            'user_id' => $this->userId,
            'policy_id' => $this->policyId,
            'accepted_at' => CarbonImmutable::now(),
        ]);
    }
}
