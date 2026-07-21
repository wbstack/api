<?php

namespace Tests\Routes;

use App\Policy;
use App\PolicyAcceptance;
use App\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PoliciesControllerTest extends TestCase {
    use DatabaseTransactions;

    private string $currentPoliciesRoute = 'v1/policies/current';

    private string $missingPoliciesRoute = 'v1/policies/missing';

    private function createPolicy(string $type, CarbonImmutable $activeFrom, string $content): Policy {
        return Policy::create([
            'policy_type' => $type,
            'active_from' => $activeFrom,
            'content_vue_file' => $content,
        ]);
    }

    public function testMissingPoliciesRequiresAuthentication(): void {
        $this->json('GET', $this->missingPoliciesRoute)
            ->assertStatus(401);
    }

    public function testGetCurrentPolicies(): void {
        $now = CarbonImmutable::now();

        // Future policy
        $this->createPolicy('terms-of-use', $now->addDay(), 'terms-of-use/version-future.vue');

        // Older active policy of the same type should be excluded
        $this->createPolicy('hosting-policy', $now->subMonths(2), 'hosting-policy/version-1.vue');

        // Active policies
        $latestActiveToUPolicy = $this->createPolicy('terms-of-use', $now->subMonth(), 'terms-of-use/version-2.vue');
        $latestActiveHostingPolicy = $this->createPolicy('hosting-policy', $now->subWeek(), 'hosting-policy/version-2.vue');

        $response = $this->json('GET', $this->currentPoliciesRoute);

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

    public function testMissingPoliciesReturnsOnlyLatestCurrentPolicyPerType(): void {
        $user = User::factory()->create();
        $now = CarbonImmutable::now();

        // Future policy (should be ignored because it is not active yet)
        $this->createPolicy('terms-of-use', $now->addDays(10), 'terms-of-use/version-future.vue');
        $olderTerms = $this->createPolicy('terms-of-use', $now->subDays(10), 'terms-of-use/version-1.vue');
        $latestTerms = $this->createPolicy('terms-of-use', $now->subDays(1), 'terms-of-use/version-2.vue');
        $this->createPolicy('terms-of-use', $now->addDays(1), 'terms-of-use/version-3.vue');

        $olderHosting = $this->createPolicy('hosting-policy', $now->subDays(20), 'hosting-policy/version-1.vue');
        $latestHosting = $this->createPolicy('hosting-policy', $now->subDays(2), 'hosting-policy/version-2.vue');

        $response = $this->actingAs($user, 'api')
            ->json('GET', $this->missingPoliciesRoute)
            ->assertStatus(200)
            ->assertJsonStructure([
                'items' => [
                    ['metadata' => ['policy_id', 'type', 'active_from', 'content_vue_file']],
                ],
            ]);

        $items = collect($response->json('items'));

        $this->assertCount(2, $items);

        $this->assertTrue($items->contains(function (array $item) use ($latestTerms): bool {
            return $item['metadata']['policy_id'] === $latestTerms->id
                && $item['metadata']['type'] === 'terms-of-use'
                && $item['metadata']['active_from'] === $latestTerms->active_from->format('Y-m-d')
                && $item['metadata']['content_vue_file'] === 'terms-of-use/version-2.vue';
        }));

        $this->assertTrue($items->contains(function (array $item) use ($latestHosting): bool {
            return $item['metadata']['policy_id'] === $latestHosting->id
                && $item['metadata']['type'] === 'hosting-policy'
                && $item['metadata']['active_from'] === $latestHosting->active_from->format('Y-m-d')
                && $item['metadata']['content_vue_file'] === 'hosting-policy/version-2.vue';
        }));

        $this->assertFalse($items->contains(function (array $item) use ($olderTerms): bool {
            return $item['metadata']['policy_id'] === $olderTerms->id;
        }));

        $this->assertFalse($items->contains(function (array $item) use ($olderHosting): bool {
            return $item['metadata']['policy_id'] === $olderHosting->id;
        }));

        $this->assertCount(1, $items->where('metadata.type', 'terms-of-use'));
        $this->assertCount(1, $items->where('metadata.type', 'hosting-policy'));
    }

    public function testMissingPoliciesExcludesAlreadyAcceptedPoliciesForCurrentUser(): void {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $now = CarbonImmutable::now();

        $terms = $this->createPolicy('terms-of-use', $now->subDays(1), 'terms-of-use/version-2.vue');
        $hosting = $this->createPolicy('hosting-policy', $now->subDays(1), 'hosting-policy/version-1.vue');

        PolicyAcceptance::create([
            'user_id' => $user->id,
            'policy_id' => $terms->id,
            'accepted_at' => $now,
        ]);

        // Acceptance by another user must not affect current user response
        PolicyAcceptance::create([
            'user_id' => $anotherUser->id,
            'policy_id' => $hosting->id,
            'accepted_at' => $now,
        ]);

        $response = $this->actingAs($user, 'api')
            ->json('GET', $this->missingPoliciesRoute)
            ->assertStatus(200);

        $items = collect($response->json('items'));

        $this->assertCount(1, $items);
        $this->assertSame($hosting->id, $items->first()['metadata']['policy_id']);
        $this->assertSame('hosting-policy', $items->first()['metadata']['type']);
    }

    public function testMissingPoliciesReturnsEmptyListWhenAllCurrentPoliciesAccepted(): void {
        $user = User::factory()->create();
        $now = CarbonImmutable::now();

        $terms = $this->createPolicy('terms-of-use', $now->subDays(1), 'terms-of-use/version-2.vue');
        $hosting = $this->createPolicy('hosting-policy', $now->subDays(1), 'hosting-policy/version-1.vue');

        PolicyAcceptance::create([
            'user_id' => $user->id,
            'policy_id' => $terms->id,
            'accepted_at' => $now,
        ]);

        PolicyAcceptance::create([
            'user_id' => $user->id,
            'policy_id' => $hosting->id,
            'accepted_at' => $now,
        ]);

        $this->actingAs($user, 'api')
            ->json('GET', $this->missingPoliciesRoute)
            ->assertStatus(200)
            ->assertJson(['items' => []]);
    }
}
