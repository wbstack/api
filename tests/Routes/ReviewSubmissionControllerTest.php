<?php

namespace Tests\Routes;

use App\ActiveSuspension;
use App\ReviewSubmission;
use App\ScheduledSuspension;
use App\Wiki;
use Carbon\CarbonImmutable;
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
        $submission = $this->createSubmittedSubmission();
        $this->json(
            'PUT',
            $this->baseRoute . '/' . $submission->id,
            $data = [
                'wiki_id' => $this->wiki->id,
                'status' => 'cancelled'
            ])
        ->assertStatus(200);
        $submission->refresh();
        $this->assertTrue( $submission->status == 'cancelled' );
    }

    public function testManagerCancelSubmissionActivatesScheduledSuspension(): void {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('first day of October 2023'));
        $submission = $this->createSubmittedSubmission();
        $scheduledSupension = ScheduledSuspension::create([
            'active_from' => '2022-01-01',
            'wiki_id' => $this->wiki->id
        ]);

        $this->json(
            'PUT',
            $this->baseRoute . '/' . $submission->id,
            $data = [
                'wiki_id' => $this->wiki->id,
                'status' => 'cancelled'
            ])
        ->assertStatus(200);
        $this->assertModelExists(ActiveSuspension::where([
            'since' => '2023-10-01',
            'wiki_id' => $this->wiki->id
            ])->firstorFail());
        $this->assertModelMissing($scheduledSupension);
    }

    public function testManagerCancelSubmissionNotActivateScheduledSuspension(): void {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('first day of October 2023'));
        $submission = $this->createSubmittedSubmission();
        $scheduledSupension = ScheduledSuspension::create([
            'active_from' => '2028-01-01',
            'wiki_id' => $this->wiki->id
        ]);

        $this->json(
            'PUT',
            $this->baseRoute . '/' . $submission->id,
            $data = [
                'wiki_id' => $this->wiki->id,
                'status' => 'cancelled'
            ])
        ->assertStatus(200);
        $this->assertEquals(0, ActiveSuspension::where([
            'since' => '2023-10-01',
            'wiki_id' => $this->wiki->id
        ])->count());
        $this->assertModelExists($scheduledSupension);
    }

    public function testGetAdminStartPendingReview(): void {
    }

    private function createSubmittedSubmission(): ReviewSubmission
    {
        $submission = new ReviewSubmission();
        $submission->wiki_id = $this->wiki->id;
        $submission->status = 'submitted';
        $submission->save();
        return $submission;
    }

}
