<?php

namespace Tests;

use App\Wiki;
use App\WikiProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WikiProfileTest extends TestCase
{
    use RefreshDatabase;

    protected Wiki $wiki;

    protected function setUp(): void {
        parent::setUp();
        $this->wiki = Wiki::factory()->create();
    }
    
    public function testCreateValidWikiProfile(): void
    {
        $profile = new WikiProfile([
            'wiki_id' => $this->wiki->id,
            'purpose' => 'data_hub',
            'audience' => 'narrow',
            'temporality' => 'permanent',
        ]);

        $profile->save();

        $this->assertDatabaseHas('wiki_profiles', [
            'wiki_id' => $this->wiki->id,
            'purpose' => 'data_hub',
            'audience' => 'narrow',
            'temporality' => 'permanent',
        ]);
    }

    public function testCreateWikiProfileWithOtherFields(): void
    {
        $profile = new WikiProfile([
            'wiki_id' => $this->wiki->id,
            'purpose' => 'other',
            'purpose_other' => 'Custom purpose',
            'audience' => 'other',
            'audience_other' => 'Custom audience',
            'temporality' => 'other',
            'temporality_other' => 'Custom temporality',
        ]);

        $profile->save();

        $this->assertDatabaseHas('wiki_profiles', [
            'wiki_id' => $this->wiki->id,
            'purpose' => 'other',
            'purpose_other' => 'Custom purpose',
            'audience' => 'other',
            'audience_other' => 'Custom audience',
            'temporality' => 'other',
            'temporality_other' => 'Custom temporality',
        ]);
    }

}