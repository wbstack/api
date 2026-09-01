<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\ReviewSubmission;
use App\User;
use App\Wiki;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReviewSubmissionControllerTest extends TestCase {
    use DatabaseTransactions;

    /**
     * Test that a review submission is created
     */
    public function testPost(): void {
        $wiki = Wiki::factory()->create();
        $user = User::factory()->create();

        // this test assumes that the Wiki factory hasn't created any review submissions
        $this->assertSame(0, $wiki->reviewSubmissions->count());

        $response = $this->actingAs($user)->post("/v1/wikis/{$wiki->id}/review_submissions");

        // $response->dump(); die;
        // {#1993
        //   +"wiki_id": "126"
        //   +"additional_information": null
        //   +"updated_at": "2026-09-01T15:20:07.000000Z"
        //   +"created_at": "2026-09-01T15:20:07.000000Z"
        //   +"id": 69
        // }

        $response->assertStatus(201);
        $response->assertJsonFragment(['wiki_id' => $wiki->id]);
        $wiki->refresh();
        $this->assertSame($wiki->reviewSubmissions->first()->id, $response->json('id'));
        $this->assertSame(1, ReviewSubmission::findOrFail($response->json('id'))->actions->count());
    }

    public function testGetWithReviewActions(): void {
        $wiki = Wiki::factory()->create();

        $submission = ReviewSubmission::factory()
            ->for($wiki)
            ->hasActions(
                ['type' => 'submitted', 'actor_user_role' => UserRole::WIKI_MANAGER],
                ['type' => 'review_started', 'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN],
                ['type' => 'approved', 'actor_user_role' => UserRole::REVIEW_COMMITTEE_ADMIN],
            )->create();

        $response = $this->get("/v1/wikis/{$wiki->id}/review_submissions/{$submission->id}");
        $response->assertStatus(200);
        var_dump($response->content());
        // TODO: implement and finish off
    }
}
