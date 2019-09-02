<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Jobs\UserCreateJob;
use Illuminate\Http\Request;
use App\Jobs\InvitationDeleteJob;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use App\Jobs\UesrVerificationTokenCreateAndSendJob;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $validation = [
          //'name' => ['required', 'string', 'max:255'],
          'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
          // TODO production set password length limit..
          'password' => ['required', 'string'/*'min:8'*/],
          // SHIFT we confirm this in JS.. do don't do it here?
          //'password' => ['required', 'string', 'min:8', 'confirmed'],
          'recaptcha' => 'required|captcha',
      ];

        // XXX: for phpunit dont validate captcha when requested....
        // TODO this should be mocked in the test instead
        if (getenv('PHPUNIT_RECAPTCHA_CHECK') == '0') {
            unset($validation['recaptcha']);
        }

        // If this is the first user then do not require an invitation or captcha
        if (User::count() === 0) {
            $inviteRequired = false;
            unset($validation['recaptcha']);
        } else {
            $inviteRequired = true;
            $validation['invite'] = 'required|exists:invitations,code';
        }

        return Validator::make($data, $validation);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        // WORK
        $user = ( new UserCreateJob(
        $data['email'],
        $data['password']
      ))->handle();
        ( new InvitationDeleteJob($data['invite']) )->handle();
        ( new UesrVerificationTokenCreateAndSendJob($user) )->handle();

        return $user;
    }

    protected function registered(Request $request, $user)
    {
        // HTTP Response
        $res['success'] = true;
        $res['message'] = 'Register Successful!';
        $res['data'] = $this->convertUserForOutput($user);

        return response($res);
    }

    // TODO why is this needed?
    // TODO the model used by the frontend stuff should just not have the password...
    protected function convertUserForOutput(User $user)
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'verified' => $user->verified,
        ];
    }
}
