<?php

namespace Tests;

use App\Policy;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PolicyTest extends TestCase {
    use RefreshDatabase;

    public function testCreatesAndSavesSuccessfully(): void {
        $yesterday = Carbon::yesterday();
        $policy = Policy::create(
            [
                'policy_type' => 'terms-of-use',
                'active_from' => $yesterday,
                'content_vue_file' => 'terms-of-use/example.vue',
            ]
        );
        $policy->save();

        $this->assertDatabaseHas('policies', [
            'policy_type' => 'terms-of-use',
            'active_from' => $yesterday,
            'content_vue_file' => 'terms-of-use/example.vue',
        ]);
    }
}
