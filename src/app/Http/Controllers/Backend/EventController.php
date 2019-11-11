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
}
