<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\InvitationDeleteJob;
use App\Jobs\UserCreateJob;
use App\Jobs\UserVerificationCreateTokenAndSendJob;
use App\Rules\ReCaptchaValidation;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /**
     * @var \App\Rules\ReCaptchaValidation
     */
    protected $recaptchaValidation;

    public function __construct(ReCaptchaValidation $recaptchaValidation) {
        $this->recaptchaValidation = $recaptchaValidation;
    }

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
            'recaptcha' => ['required', 'string', 'bail', $this->recaptchaValidation],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'  => ['required', 'string', 'min:8'],
        ];
        return Validator::make($data, $validation);
    }
}
