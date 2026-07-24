<?php

namespace Tests\Resources;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Resources\PolicyResource;
use App\Policy;

class PolicyResourceTest extends TestCase {
    use RefreshDatabase;

    public function testActiveFrom(): void {
        $policy = Policy::create([
            'policy_type' => 'terms-of-use',
            'content_vue_file' => 'terms-of-use/example.vue',
        ]);

        $resource = new PolicyResource($policy);
        $data = json_decode($resource->toJson());

        $this->assertEquals(
            data_get($data, 'metadata.active_from'),
            null,
        );
    }
}
