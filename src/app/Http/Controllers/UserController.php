<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Validator;
use App\User;
use App\Invitation;
use App\UserVerificationToken;
use App\Jobs\EmailVerificationJob;
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
          // TODO validate password length when not deving...
            'email' => 'required|email|unique:users',
            'password' => 'required',
            'recaptcha' => 'required|captcha',
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

        // TODO should be able to create new one without passing in token?
        $emailToken = bin2hex(random_bytes(24));
        UserVerificationToken::create([
          'user_id' => $user->id,
          'token' => $emailToken,
        ]);
        dispatch(new EmailVerificationJob($user, $emailToken));

        $res['success'] = true;
        $res['message'] = 'Register Successful!';
        $res['data'] = $this->convertUserForOutput( $user );
        return response($res);
    }

    public function getSelf( Request $request ) {
      $user = $request->user();
        if ( $user ) {
            $res['success'] = true;
            // Filter what we give to the user
            $res['message'] = $this->convertUserForOutput( $user );

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
