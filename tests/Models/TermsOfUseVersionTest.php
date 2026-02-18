<?php

namespace Tests\Models;

use App\TermsOfUseVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TermsOfUseVersionTest extends TestCase {
    use RefreshDatabase;

    public function testSavingActiveVersionDeactivatesOtherVersions(): void {
        $first = TermsOfUseVersion::create([
            'version' => '2024-01-01',
            'active' => true,
        ]);

        $second = TermsOfUseVersion::create([
            'version' => '2025-01-01',
            'active' => true,
        ]);

        $this->assertFalse($first->fresh()->active);
        $this->assertTrue($second->fresh()->active);
    }

    public function testSavingInactiveVersionDoesNotAffectExistingActiveVersion(): void {
        $active = TermsOfUseVersion::create([
            'version' => '2024-03-01',
            'active' => true,
        ]);

        TermsOfUseVersion::create([
            'version' => '2024-04-01',
            'active' => false,
        ]);

        $this->assertTrue($active->fresh()->active);
    }
}
