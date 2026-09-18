<?php

namespace App\Http\Controllers;

use App\ActiveSuspension;
use Illuminate\Http\Request;

class ActiveSuspensionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Possible for anyone
        // TODO: implement
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Only possible for admin
        return ActiveSuspension::create([
            'since' => $request->input('since'),
            'wiki_id' => $request->input('wiki_id')
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(ActiveSuspension $activeSuspension)
    {
        // Possible for anyone
        // TODO: implement
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ActiveSuspension $activeSuspension)
    {
        // Only possible for admin
        // TODO: implement
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ActiveSuspension $suspension)
    {
        $suspension->first()->delete();
    }
}
