<?php

namespace Tests\Routes;

use App\ActiveSuspension;
use App\ReviewSubmission;
use App\Wiki;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReviewSubmissionControllerTest extends TestCase {
    protected $baseRoute = 'v1/reviewSubmission';

    private Wiki $wiki;

    use DatabaseTransactions;

    protected function setUp(): void {
        parent::setUp();
        $this->wiki = Wiki::factory()->create();
    }

    protected function tearDown(): void {
        parent::tearDown();
    }

    public function testManagerCreateSubmission(): void {
        $this->json(
            'POST',
            $this->baseRoute,
            $data = [
                'wiki_id' => $this->wiki->id
            ]);

        $expectedSubmission = ReviewSubmission::where(
            [
                'wiki_id' => $this->wiki->id,
                'status' => 'submitted'
            ]
        );
        $this->assertTrue($expectedSubmission->exists());
    }

    public function testManagerCancelSubmission(): void {

    }

    public function testGetAdminStartPendingReview(): void {

    }

}
