<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Validator;
use App\User;
use App\Invitation;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class UserController extends BaseController
{
    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    private $request;
    /**
     * Create a new controller instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function create(Request $request)
    {
        $validation = [
            'email' => 'required|email|unique:users',
            'password' => 'required'
        ];

        // If this is the first user then do not require an invitation
        if( User::count() === 0 ) {
          $inviteRequired = false;
        } else {
          $inviteRequired = true;
          $validation['invite'] = 'required';
        }

        $this->validate($request, $validation);

        if( $inviteRequired ) {
          $invite = Invitation::where('code', $request->input('invite'))->first();
          if(!$invite) {
            $res['invite'] = ['Invite code not valid'];
            return response($res)->setStatusCode(422);
          }
        }

        $email = $request->input('email');
        $password = Hash::make( $request->input('password') );
        $user = User::create([
            'email' => $email,
            'password' => $password,
        ]);

        // If we required and checked an invite, then delete it.
        if( $inviteRequired && $invite ) {
          $invite->delete();
        }

        $res['success'] = true;
        $res['message'] = 'Register Successful!';
        $res['data'] = $this->convertUserForOutput( $user );
        return response($res);
    }

    public function getSelf( Request $request ) {
        if ( isset( $request->auth ) ) {
            $res['success'] = true;
            // Filter what we give to the user
            $res['message'] = $this->convertUserForOutput( $request->auth );

            return response($res);
        }

        abort(404);

        return response($res);
    }

    // TODO why is this needed?
    protected function convertUserForOutput ( User $user ) {
        return [
            'id' => $user->id,
            'email' => $user->email,
        ];
    }

}
