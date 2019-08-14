<?php

namespace App\Http\Controllers;

use App\Invitation;
use Illuminate\Http\Request;

class InvitationController extends Controller
{

    private function getAndRequireAuthedUser( Request $request ) {
      if(!$request->auth) {
        // This is a logic exception as the router / JWT middleware requires a user already
        throw new LogicException("Controller should not be run without auth");
      }
      return $request->auth;
    }

    private function requireAdam( $user ) {
      // TODO this should be done with permissions and middleware...
      if( $user->email != 'adamshorland@gmail.com' ) {
        throw new RuntimeException('A required!');
      }
    }

    public function create( Request $request ){
        $user = $this->getAndRequireAuthedUser( $request );
        $this->requireAdam($user);

        $this->validate($request, [
            'code' => 'required|unique:invitations',
        ]);
        $code = $request->input('code');

        $test = Invitation::where('code', $code)->first();
        if($test) {
          $res['success'] = false;
          $res['message'] = 'Code already exists.';
          return response($res);
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
        $user = $this->getAndRequireAuthedUser( $request );
        $this->requireAdam($user);

        $this->validate($request, [
          //TODO is these a special validate for exists?
            'code' => 'required',
        ]);
        $code = $request->input('code');

        $test = Invitation::where('code', $code)->first();
        if(!$test) {
          $res['success'] = false;
          $res['message'] = 'Code did not exist.';
          return response($res);
        }

        // TODO check response of this method call?
        $test->delete();

        $res['success'] = true;
        $res['message'] = 'Code deleted!';
        $res['code'] = $code;
        return response($res);
    }

    public function list( Request $request ){
        $user = $this->getAndRequireAuthedUser( $request );
        $this->requireAdam($user);

        $result = Invitation::all();

        $res['success'] = true;
        $res['data'] = $result;
        return response($res);
    }

}
