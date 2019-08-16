<?php

namespace App\Http\Controllers\Admin;

use App\Invitation;
use App\Http\Controllers\Controller;
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
          //TODO is these a special validate for exists?
            'code' => 'required',
        ]);
        $code = $request->input('code');

        $test = Invitation::where('code', $code)->first();
        if(!$test) {
          abort(404);
        }

        // TODO check response of this method call?
        $test->delete();

        $res['success'] = true;
        $res['message'] = 'Code deleted!';
        $res['code'] = $code;
        return response($res);
    }

}
