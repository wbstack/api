<?php

namespace App\Http\Controllers\Admin;

use App\Invitation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class InvitationsController extends Controller
{
    public function get(Request $request)
    {
        $result = Invitation::all();

        $res['success'] = true;
        $res['data'] = $result;

        return response($res);
    }
}
