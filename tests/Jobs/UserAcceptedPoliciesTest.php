<?php

namespace Tests\Jobs;

use App\Jobs\UserCreateJob;
use App\Policy;
use App\PolicyAcceptance;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAcceptedPoliciesTest extends TestCase {
    use RefreshDatabase;

    public function testUserRegistrationAcceptsNone(): void {
        $email = 'test+' . uniqid('', true) . '@example.com';
        $user = (new UserCreateJob($email, 'thisisapassword123', null, true))->handle();

        $this->assertEquals(
            PolicyAcceptance::all()->count(),
            0
        );
    }

    public function testUserRegistrationAcceptsOne(): void {
        $termsOfUse = new Policy();
        $termsOfUse->policy_type = 'terms-of-use';
        $termsOfUse->active_from = CarbonImmutable::now();
        $termsOfUse->content_vue_file = 'termsOfUse.vue';
        $termsOfUse->save();

        $email = 'test+' . uniqid('', true) . '@example.com';
        $user = (new UserCreateJob($email, 'thisisapassword123', [$termsOfUse->id], true))->handle();

        $this->assertDatabaseHas('policy_acceptances', [
            'user_id' => $user->id,
            'policy_id' => $termsOfUse->id,
        ]);
    }

    public function testUserRegistrationAcceptsTwo(): void {
        $termsOfUse = new Policy();
        $termsOfUse->policy_type = 'terms-of-use';
        $termsOfUse->active_from = CarbonImmutable::now();
        $termsOfUse->content_vue_file = 'termsOfUse.vue';
        $termsOfUse->save();

        $hostingPolicy = new Policy();
        $hostingPolicy->policy_type = 'hosting-policy';
        $hostingPolicy->active_from = CarbonImmutable::now();
        $hostingPolicy->content_vue_file = 'hostingPolicy.vue';
        $hostingPolicy->save();

        $email = 'test+' . uniqid('', true) . '@example.com';
        $user = (new UserCreateJob($email, 'thisisapassword123', [$termsOfUse->id, $hostingPolicy->id], true))->handle();

        $this->assertDatabaseHas('policy_acceptances', [
            'user_id' => $user->id,
            'policy_id' => $termsOfUse->id,
        ]);

        $this->assertDatabaseHas('policy_acceptances', [
            'user_id' => $user->id,
            'policy_id' => $hostingPolicy->id,
        ]);
    }
}
