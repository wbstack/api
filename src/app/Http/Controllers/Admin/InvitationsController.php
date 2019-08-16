<?php

namespace App\Http\Controllers\Admin;

use App\Invitation;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InvitationsController extends Controller
{

    public function get( Request $request ){
        $result = Invitation::all();

        $res['success'] = true;
        $res['data'] = $result;
        return response($res);
    }

}
