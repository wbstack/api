<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\InvitationDeleteJob;
use App\Jobs\UserCreateJob;
use App\Jobs\UserVerificationCreateTokenAndSendJob;
use App\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Rules\Recaptcha;

class RegisterController extends Controller
{
    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        $user = null;
        DB::transaction(function () use (&$user, $request) {
            $user = ( new UserCreateJob(
            $request->input('email'),
            $request->input('password')
          ))->handle();
            if ($request->input('invite')) {
                ( new InvitationDeleteJob($request->input('invite')) )->handle();
            }
            (UserVerificationCreateTokenAndSendJob::newForAccountCreation($user))->handle();
        });

        if ($user === null) {
            // Code probably shouldnt ever get here..? As the transaction might throw? maybe?
            throw new \LogicException('Oh noes!');
        }

        event(new Registered($user));

        Auth::guard()->login($user);

        // HTTP Response
        $res['success'] = true;
        $res['message'] = 'Register Successful!';
        $res['data'] = $user;

        return response($res);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $validation = [
            'recaptcha' => ['required', 'string', 'bail', new Recaptcha],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'  => ['required', 'string', 'min:8'],
            'invite'    => ['required', 'string', 'exists:invitations,code']
      ];

        // XXX: for phpunit dont validate captcha when requested....
        // TODO this should be mocked in the test instead
        if (getenv('PHPUNIT_RECAPTCHA_CHECK') === '1') {
            unset($validation['recaptcha']);
        }

        // If this is the first user then do not require an invitation or captcha
        if (User::count() === 0) {
            unset($validation['recaptcha']);
            unset($validation['invite']);
        }

        return Validator::make($data, $validation);
    }
}
