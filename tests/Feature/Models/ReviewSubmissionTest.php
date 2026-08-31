<?php

// TODO: move models and tests to Models namespaces

namespace Tests\Feature\Models;

use App\ReviewSubmission;
use App\Wiki;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReviewSubmissionTest extends TestCase {
    use DatabaseTransactions;

    public function testCreation(): void {
        $wiki = Wiki::factory()->create();
        $submission = ReviewSubmission::make([
            'wiki_id' => $wiki->id,
            'additional_information' => 'This ReviewSubmission was created with a Factory. ' . fake()->paragraph(),
        ]);

        $this->assertTrue($submission->save());
    }
}
