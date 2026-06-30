<?php

namespace Database\Seeders;

use App\Policy;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class PolicySeeder extends Seeder {
    public function run() {
        Policy::create(
            [
                'policy_type' => 'terms-of-use',
                'active_from' => CarbonImmutable::createFromDate(2026, 06, 01),
                'content_vue_file' => 'terms-of-use/example.vue',
            ]
        );
    }
}
