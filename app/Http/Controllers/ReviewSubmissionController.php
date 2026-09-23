<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ReviewSubmissionActionType;
use App\Enums\UserRole;
use App\ReviewSubmission;
use App\Wiki;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
     *
     * @return JsonResource<ReviewSubmission>
     *
     * @throws HttpException
     */
    public function store(Request $request, Wiki $wiki): JsonResource {
        $request->validate([
            'additional_information' => ['sometimes', 'string', 'nullable', 'max:1000'],
        ]);

        $submission = DB::transaction(function () use ($request, $wiki) {
            $hasOpenSubmission = ReviewSubmission::query()
                ->whereWikiId($wiki->id)
                ->whereHas('latestAction', function ($query) {
                    $query->whereIn('type', [
                        ReviewSubmissionActionType::SUBMITTED,
                        ReviewSubmissionActionType::REVIEW_STARTED,
                    ]);
                })->exists();

            if ($hasOpenSubmission) {
                throw new HttpException(
                    Response::HTTP_CONFLICT,
                    'There is already an open Review Submission for this Wiki.',
                );
            }

            $submission = new ReviewSubmission();
            $submission->wiki_id = $wiki->id;
            $submission->additional_information = $request->input('additional_information');
            $submission->save();

            $submission->actions()->create([
                'type' => ReviewSubmissionActionType::SUBMITTED,
                'actor_user_id' => $request->user()->id,
                'actor_user_role' => UserRole::WIKI_MANAGER,
            ]);

            // load the `latestAction` relationship so that the response matches the other methods
            $submission->refresh()->load('latestAction');

            return $submission;
        });

        // Return a JsonResource so that the ReviewSubmission is wrapped in a 'data' field
        // without having to create a stock ReviewSubmissionResource class.
        return JsonResource::make($submission)->additional(['success' => true]);
    }

    /**
     * Display the specified ReviewSubmission.
     */
    public function show(Wiki $wiki, ReviewSubmission $review_submission) {
        // Return a JsonResource so that the ReviewSubmission is wrapped in a 'data' field
        // without having to create a stock ReviewSubmissionResource class.
        return JsonResource::make($review_submission)->additional(['success' => true]);
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
