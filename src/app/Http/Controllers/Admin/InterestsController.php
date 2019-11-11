<?php

namespace App\Http\Controllers\Admin;

use App\Interest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class InterestsController extends Controller
{
    public function get(Request $request)
    {
        $result = Interest::all();

        $res['success'] = true;
        $res['data'] = $result;

        return response($res);
    }
}
