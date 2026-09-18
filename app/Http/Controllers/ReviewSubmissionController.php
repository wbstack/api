<?php

namespace App\Http\Controllers;

use App\ActiveSuspension;
use App\ReviewSubmission;
use App\ScheduledSuspension;
use App\Wiki;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        if ($request->input('status') === 'cancelled') {
            $loadedSubmission = $reviewSubmission->get()->first();
            $loadedSubmission->status = $request->input('status');

            DB::transaction( function () use ($loadedSubmission)  {
                $wiki = $loadedSubmission->wiki;
                $loadedSubmission->save();
                $this->enactAnyPendingScheduledSuspensionsOnCancellation($wiki);
            });
        }
    }

    private function enactAnyPendingScheduledSuspensionsOnCancellation( Wiki $wiki ): void {
        // var_dump($wiki);
        $scheduledSuspension = ScheduledSuspension::where(['wiki_id' => $wiki->id])->first();
        // var_dump($scheduledSuspension);
        if( $scheduledSuspension && $scheduledSuspension->schedulingDue()) {
            ActiveSuspension::create([
                'wiki_id' => $wiki->id,
                'since' => CarbonImmutable::now(),
                'reason' => $scheduledSuspension->reason
            ]);
            $scheduledSuspension->delete();
    }
//if there are any scheduled suspensions
//and they should have been processed
//make them active and remove the scheduled suspension
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReviewSubmission $reviewSubmission)
    {
        // Possible for admin only
    }
}
