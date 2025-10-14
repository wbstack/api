<?php

namespace Tests\Jobs;

use App\Jobs\CreateFirstTermsOfUseVersionJob;
use App\Jobs\UserTouAcceptanceJob;
use App\TermsOfUseVersion;
use App\User;
use App\UserTermsOfUseAcceptance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CreateFirstTermsOfUseVersionJobTest extends TestCase {
    use RefreshDatabase;

    public function testCreateFirstTermsOfUseVersionJob(): void {
        $this->assertDatabaseCount('tou_versions', 0);

        (new CreateFirstTermsOfUseVersionJob)->handle();

        $this->assertDatabaseHas('tou_versions', [
            'version' => 'v0',
            'active' => true,
            'acceptance_deadline' => null,
            'content' => null,
        ]);
    }
}
