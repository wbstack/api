<?php

namespace Tests\Routes;

use App\Policy;
use App\PolicyAcceptance;
use App\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PolicyAcceptanceControllerTest extends TestCase {
    protected $route = 'policy_acceptances';

    use DatabaseTransactions;

    private function makePolicy(string $type = 'terms-of-use'): Policy {
        $policy = new Policy();
        $policy->policy_type = $type;
        $policy->active_from = CarbonImmutable::now();
        $policy->content_vue_file = $type . '/version-1.vue';
        $policy->save();

        return $policy;
    }

    public function testUnauthenticatedRequestResponds401(): void {
        $this->json('PUT', $this->route)
            ->assertStatus(401);
    }

    public function testAcceptSinglePolicy(): void {
        $user = User::factory()->create();
        $policy = $this->makePolicy();

        $this->actingAs($user, 'api')
            ->json('PUT', $this->route, ['policy_ids' => [$policy->id]])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('policy_acceptances', [
            'user_id' => $user->id,
            'policy_id' => $policy->id,
        ]);
    }

    public function testAcceptMultiplePolicies(): void {
        $user = User::factory()->create();
        $termsOfUse = $this->makePolicy('terms-of-use');
        $hostingPolicy = $this->makePolicy('hosting-policy');

        $this->actingAs($user, 'api')
            ->json('PUT', $this->route, ['policy_ids' => [$termsOfUse->id, $hostingPolicy->id]])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('policy_acceptances', ['user_id' => $user->id, 'policy_id' => $termsOfUse->id]);
        $this->assertDatabaseHas('policy_acceptances', ['user_id' => $user->id, 'policy_id' => $hostingPolicy->id]);
    }

    public function testAlreadyAcceptedPolicyIsIgnored(): void {
        $user = User::factory()->create();
        $policy = $this->makePolicy();

        PolicyAcceptance::create([
            'user_id' => $user->id,
            'policy_id' => $policy->id,
            'accepted_at' => now(),
        ]);

        $this->actingAs($user, 'api')
            ->json('PUT', $this->route, ['policy_ids' => [$policy->id]])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSame(1, PolicyAcceptance::where([
            'user_id' => $user->id,
            'policy_id' => $policy->id,
        ])->count());
    }

    public function testNonExistentPolicyIdReturns400(): void {
        $user = User::factory()->create();
        $policy = $this->makePolicy();
        $nonExistentId = 999999;

        $this->actingAs($user, 'api')
            ->json('PUT', $this->route, ['policy_ids' => [$policy->id, $nonExistentId]])
            ->assertStatus(400)
            ->assertJsonFragment(['success' => false])
            ->assertJsonFragment(['missing_policy_ids' => [$nonExistentId]]);

        // Nothing should have been written
        $this->assertDatabaseMissing('policy_acceptances', [
            'user_id' => $user->id,
            'policy_id' => $policy->id,
        ]);
    }

    public function testMissingPolicyIdsFieldReturns422(): void {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->json('PUT', $this->route, [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['policy_ids']]);
    }

    public function testPolicyIdsNotAnArrayReturns422(): void {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->json('PUT', $this->route, ['policy_ids' => 'not-an-array'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['policy_ids']]);
    }
}
