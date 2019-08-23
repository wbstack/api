<?php

namespace App\Http\Controllers\Admin;

use App\Invitation;
use App\Http\Controllers\Controller;
use App\Jobs\InvitationDeleteJob;
use Illuminate\Http\Request;

class InvitationController extends Controller
{

    public function create( Request $request ){
        $this->validate($request, [
            'code' => 'required|unique:invitations',
        ]);
        $code = $request->input('code');

        $test = Invitation::where('code', $code)->first();
        if($test) {
          abort(409);//conflict
        }

        $invite = Invitation::create([
            'code' => $code,
        ]);

        $res['success'] = true;
        $res['message'] = 'Code created!';
        $res['code'] = $code;
        return response($res);
    }

    public function delete( Request $request ){
        $this->validate($request, [
          //TODO do we want to validate that this exists?
            'code' => 'required',
        ]);

        ( new InvitationDeleteJob( $request->input('code') ) )->handle();

        // TODO do we actually need all of this response? or remove it?
        $res['success'] = true;
        $res['message'] = 'Code deleted!';
        $res['code'] = $request->input('code');
        return response($res);
    }

}
