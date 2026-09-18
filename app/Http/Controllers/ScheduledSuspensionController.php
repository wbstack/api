<?php

namespace App\Http\Controllers;

use App\ScheduledSuspension;
use App\Wiki;
use Illuminate\Http\Request;

class ScheduledSuspensionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return ScheduledSuspension::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        return ScheduledSuspension::create([
            'active_from' => $request->input('active_from'),
            'wiki_id' => $request->input('wiki_id')
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show( ScheduledSuspension $suspension )
    {
        return $suspension->get();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ScheduledSuspension $suspension)
    {
        $suspension->first()->update([
            'active_from' => $request->input('active_from')
        ]);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ScheduledSuspension $suspension)
    {
        $suspension->first()->delete();
    }

    public function showByWiki( Wiki $wiki ) {
        return $wiki->first()->scheduledSuspension;
    }

}
