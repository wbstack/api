<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ReviewSubmissionActionType;
use App\Enums\UserRole;
use App\ReviewSubmission;
use App\Wiki;
use DB;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use RuntimeException;

class ReviewSubmissionController extends Controller {
    /**
     * Display a listing of ReviewSubmissions for the given Wiki.
     *
     * @return LengthAwarePaginator<int, ReviewSubmission>
     */
    public function index(Request $request, Wiki $wiki): LengthAwarePaginator {
        $validated = $request->validate([
            'order' => ['sometimes', 'in:asc,desc'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $order = $validated['order'] ?? 'desc';
        $perPage = $validated['per_page'] ?? 10;

        return ReviewSubmission::whereWikiId($wiki->id)
            ->withOnly('latestAction')
            ->orderBy('id', $order)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Store a newly created ReviewSubmission for the given Wiki in storage.
     */
    public function store(Request $request, Wiki $wiki): ReviewSubmission {
        $request->validate([
            'additional_information' => ['sometimes', 'string', 'nullable', 'max:1000'],
        ]);

        // TODO: do we need to check that a ReviewSubmission can be created for the given Wiki?
        // e.g. if there is already an open ReviewSubmission or the wiki won't be suspended for over 3 months

        $submission = DB::transaction(function () use ($request, $wiki) {
            $submission = new ReviewSubmission();
            $submission->wiki_id = $wiki->id;
            $submission->additional_information = $request->input('additional_information');
            $submission->save();

            $submission->actions()->create([
                'type' => ReviewSubmissionActionType::SUBMITTED,
                'actor_user_id' => $request->user()->id,
                'actor_user_role' => UserRole::WIKI_MANAGER,
            ]);

            return $submission;
        });

        return $submission;
    }

    /**
     * Display the specified ReviewSubmission.
     */
    public function show(Wiki $wiki, ReviewSubmission $review_submission) {
        return $review_submission;
    }

    /**
     * Update the specified ReviewSubmission in storage.
     */
    public function update(Request $request, ReviewSubmission $review_submission) {
        throw new RuntimeException('Not implemented yet');
    }

    /**
     * Remove the specified ReviewSubmission from storage.
     */
    public function destroy(ReviewSubmission $review_submission) {
        throw new RuntimeException('Not implemented yet');
    }
}
