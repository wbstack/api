<?php

namespace App\Http\Controllers;

use App\ReviewSubmission;
use Illuminate\Http\Request;

class ReviewSubmissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Only possible for Admin
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Possible for Admin or Authed user
        // Authed user can only create pending "submitted" status
        $submission = new ReviewSubmission();
        $submission->status = 'submitted';
        $submission->wiki_id = $request->input('wiki_id');
        $submission->save();
    }

    /**
     * Display the specified resource.
     */
    public function show(ReviewSubmission $reviewSubmission)
    {
        // Possible for admin or specifically authed user
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ReviewSubmission $reviewSubmission)
    {
        // Only possible for admin to pick up/approv, normal Wikimanager can cancel

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReviewSubmission $reviewSubmission)
    {
        // Possible for admin only
    }
}
