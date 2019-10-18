<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\InvitationCreateJob;
use App\Jobs\InvitationDeleteJob;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:invitations',
        ]);

        ( new InvitationCreateJob($request->input('code')) )->handle();

        $res['success'] = true;
        $res['message'] = 'Code created!';
        $res['code'] = $request->input('code');

        return response($res);
    }

    public function delete(Request $request)
    {
        $request->validate([
          //TODO do we want to validate that this exists?
            'code' => 'required',
        ]);

        ( new InvitationDeleteJob($request->input('code')) )->handle();

        // TODO do we actually need all of this response? or remove it?
        $res['success'] = true;
        $res['message'] = 'Code deleted!';
        $res['code'] = $request->input('code');

        return response($res);
    }
}
