<?php

namespace App\Http\Controllers;

use App\Invitation;
use Illuminate\Http\Request;

class InvitationsController extends Controller
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

    public function get( Request $request ){
        $user = $this->getAndRequireAuthedUser( $request );
        $this->requireAdam($user);

        $result = Invitation::all();

        $res['success'] = true;
        $res['data'] = $result;
        return response($res);
    }

}
