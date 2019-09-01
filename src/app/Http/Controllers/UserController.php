<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Validator;
use App\User;
use App\Invitation;
use App\UserVerificationToken;
use App\Jobs\UesrVerificationTokenCreateAndSendJob;
use App\Jobs\UserCreateJob;
use App\Jobs\InvitationDeleteJob;
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
        // HTTP validation
        $validation = [
          // TODO validate password length when not deving...
            'email' => 'required|email|unique:users',
            'password' => 'required',
            'recaptcha' => 'required|captcha',
        ];

        // XXX: for phpunit dont validate captcha when requested....
        // TODO this should be mocked in the test instead
        if(getenv('PHPUNIT_RECAPTCHA_CHECK') == '0') {
          unset($validation['recaptcha']);
        }

        // If this is the first user then do not require an invitation or captcha
        if( User::count() === 0 ) {
          $inviteRequired = false;
          unset($validation['recaptcha']);
        } else {
          $inviteRequired = true;
          $validation['invite'] = 'required|exists:invitations,code';
        }

        $this->validate($request, $validation);

        // WORK
        $user = ( new UserCreateJob(
          $request->input('email'),
          $request->input('password')
        ))->handle();
        ( new InvitationDeleteJob( $request->input('invite') ) )->handle();
        ( new UesrVerificationTokenCreateAndSendJob( $user ) )->handle();

        // HTTP Response
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
    // TODO the model used by the frontend stuff should just not have the password...
    protected function convertUserForOutput ( User $user ) {
        return [
            'id' => $user->id,
            'email' => $user->email,
        ];
    }

}
