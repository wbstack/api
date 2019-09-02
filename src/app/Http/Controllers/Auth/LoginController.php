<?php

namespace App\Http\Controllers\Auth;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;

class LoginController extends Controller
{
    use ThrottlesLogins;

    // Used by ThrottlesLogins
    protected function username() {
      return 'email';
    }

    public function login(Request $request)
    {
      // Validation
        $rules = [
          'email' => 'required|exists:users',
          'password'  => 'required',
      ];
        $request->validate($rules);

        // Throttle
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        // Try login
        $data = [
          'email' => $request->get('email'),
          'password'  =>  $request->get('password'),
          //'is_active' => true
      ];
        if (Auth::attempt($data)) {

            $this->clearLoginAttempts($request);

            $user = Auth::user();
            return response()->json([
              'user'  =>  $user, // <- we're sending the user info for frontend usage
              'token' =>  $user->createToken('yourAppName')->accessToken, // <- token is generated and sent back to the front end
              // TODO SHIFT, old UI uses these, so keep adding them for now, but should use user key?
              'email' => $user->email,
              'isAdmin' => $user->isAdmin(),
          ]);
        } else {
            $this->incrementLoginAttempts($request);
            return response()->json('Unauthorized', 401);
        }
    }
}
