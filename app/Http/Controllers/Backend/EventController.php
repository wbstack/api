<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

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
