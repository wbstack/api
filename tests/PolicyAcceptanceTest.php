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
        $policy = Policy::create([
            'policy_type' => 'terms-of-use',
            'active_from' => CarbonImmutable::yesterday(),
            'content_vue_file' => 'terms-of-use/example.vue',
        ]);
        $this->policyId = $policy->id;
    }

    protected function tearDown(): void {
        parent::tearDown();

        // clear any mocking of CarbonImmutable after each test
        CarbonImmutable::setTestNow();
    }

    public function testCreatesAndSavesSuccessfully(): void {
        $policyAcceptance = new PolicyAcceptance([
            'user_id' => $this->userId,
            'policy_id' => $this->policyId,
            'accepted_at' => CarbonImmutable::now(),
        ]);
        $policyAcceptance->save();
        $policyAcceptance->refresh();

        $this->assertDatabaseHas('policy_acceptances', [
            'user_id' => $this->userId,
            'policy_id' => $this->policyId,
        ]);

        $this->assertNotEmpty($policyAcceptance->accepted_at);
        $this->assertInstanceOf(CarbonImmutable::class, $policyAcceptance->accepted_at);
    }

    // TODO: Quickly testing all different ways of creating and saving a model.
    // TODO: Is there a better way to do this e.g. with a `@dataProvider`?
    // TODO: Do we need all the options now that we have verified that they all work? Feels like testing Laravel's `Model::creating()` event works.
    public function testAcceptedAtIsSetOnCreateIfMissing() {
        $knownDate = CarbonImmutable::create(2026, 06, 01);
        CarbonImmutable::setTestNow($knownDate);

        $policyAcceptance = PolicyAcceptance::create([
            'user_id' => $this->userId,
            'policy_id' => $this->policyId,
        ]);
        $this->assertEquals($knownDate, $policyAcceptance->accepted_at);

        $policyAcceptance->delete();

        $policyAcceptance = PolicyAcceptance::make([
            'user_id' => $this->userId,
            'policy_id' => $this->policyId,
        ]);
        $policyAcceptance->save();
        $this->assertEquals($knownDate, $policyAcceptance->accepted_at);

        $policyAcceptance->delete();

        $policyAcceptance = new PolicyAcceptance([
            'user_id' => $this->userId,
            'policy_id' => $this->policyId,
        ]);
        $policyAcceptance->save();
        $this->assertEquals($knownDate, $policyAcceptance->accepted_at);

        $policyAcceptance->delete();

        $policyAcceptance = new PolicyAcceptance();
        $policyAcceptance->user_id = $this->userId;
        $policyAcceptance->policy_id = $this->policyId;
        $policyAcceptance->save();
        $this->assertEquals($knownDate, $policyAcceptance->accepted_at);

        $policyAcceptance->delete();

        $policyAcceptance = new PolicyAcceptance();
        $policyAcceptance->fill([
            'user_id' => $this->userId,
            'policy_id' => $this->policyId,
        ]);
        $policyAcceptance->save();
        $this->assertEquals($knownDate, $policyAcceptance->accepted_at);
    }

    public function testAcceptedAtIsSetOnCreateIfNull() {
        $knownDate = CarbonImmutable::create(2026, 06, 01);
        CarbonImmutable::setTestNow($knownDate);

        $policyAcceptance = PolicyAcceptance::create([
            'user_id' => $this->userId,
            'policy_id' => $this->policyId,
            'accepted_at' => null,
        ]);

        $this->assertEquals($knownDate, $policyAcceptance->accepted_at);
    }

    public function testAcceptedAtIsNotModifiedOnUpdate() {
        $knownDate = CarbonImmutable::create(2026, 06, 01);
        CarbonImmutable::setTestNow($knownDate);

        $policyAcceptance = PolicyAcceptance::create([
            'user_id' => $this->userId,
            'policy_id' => $this->policyId,
        ]);

        $nextDay = $knownDate->addDay();
        CarbonImmutable::setTestNow($nextDay);
        $user = User::factory()->create();

        $policyAcceptance->user_id = $user->id;
        $policyAcceptance->save();

        $this->assertEquals($knownDate, $policyAcceptance->accepted_at);
        $this->assertEquals($knownDate, $policyAcceptance->created_at);
        $this->assertEquals($nextDay, $policyAcceptance->updated_at);
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
