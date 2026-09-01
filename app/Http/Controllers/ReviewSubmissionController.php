<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\ReviewSubmission;
use Illuminate\Http\Request;

class ReviewSubmissionController extends Controller {
    /**
     * Display a listing of the resource.
     */
    public function index() {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, int $wiki_id) {
        // - Validate ?
        // - Take the request, which has the wiki_id and the additional_information
        // - make ReviewSubmission and persist it
        // - make the ReviewSubmissionAction (or figure out the wiring for it to be automatic)
        //   - We could do something like this draft PR where we use static::creating() or static::created()
        //     to create the action if it hasn't already
        //     https://github.com/wbstack/api/pull/1181/changes#diff-8360ee2615422ba8e87fd8f1ff14e795ac857808c0c7e0d5fc72989ea97caedbR41-R45
        // - return something (the whole review submission or something less?)

        $submission = ReviewSubmission::create([
            'wiki_id' => $wiki_id,
            'additional_information' => $request->input('additional_information'),
        ]);

        // Just for testing to see if 'actions' are shown in the response body
        // TODO: Need to think if there is a better way to do this.
        // At the very least we should wrap both of these in a DB transaction?
        // TODO: should we create a `ReviewSubmission::addAction()` method so we don't have to add these to $fillable?
        $submission->actions()->create([
            'type' => 'submitted',
            'actor_user_id' => $request->user()->id,
            'actor_user_role' => 'wiki_manager',
        ]);

        return $submission;
    }

    /**
     * Display the specified resource.
     */
    public function show(ReviewSubmission $reviewSubmission) {
        return $reviewSubmission;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ReviewSubmission $reviewSubmission) {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReviewSubmission $reviewSubmission) {
        //
    }
}
