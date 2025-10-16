<?php

namespace Tests\Jobs;

use App\Jobs\CreateFirstTermsOfUseVersionJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateFirstTermsOfUseVersionJobTest extends TestCase {
    use RefreshDatabase;

    public function testCreateFirstTermsOfUseVersionJob(): void {
        $this->assertDatabaseCount('tou_versions', 0);

        (new CreateFirstTermsOfUseVersionJob)->handle();

        $this->assertDatabaseHas('tou_versions', [
            'version' => '2022-01-01',
            'active' => true,
        ]);
    }
}
