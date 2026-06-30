<?php

namespace Tests;

use App\Policy;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PolicyTest extends TestCase {
    use RefreshDatabase;

    public function testCreatesSuccessfully(): void {
        Policy::create(
            [
                'policy_type' => 'terms-of-use',
                'active_from' => CarbonImmutable::createMidnightDate(2025, 4, 1),
                'content_vue_file' => 'terms-of-use/example.vue',
            ]
        );

        $this->assertDatabaseHas('policies', [
            'policy_type' => 'terms-of-use',
            'active_from' => '2025-04-01',
            'content_vue_file' => 'terms-of-use/example.vue',
        ]);
    }
}
