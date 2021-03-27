<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function pageUpdate(Request $request)
    {
        \App\EventPageUpdate::create(json_decode($request->getContent(), true));
    }

    public function pageUpdateBatch(Request $request)
    {
        \App\EventPageUpdate::insert(json_decode($request->getContent(), true));
    }
}
