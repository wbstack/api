<?php

namespace App\Http\Controllers\Admin;

use App\Interest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InterestsController extends Controller
{

    public function get( Request $request ){
        $result = Interest::all();

        $res['success'] = true;
        $res['data'] = $result;
        return response($res);
    }

}
