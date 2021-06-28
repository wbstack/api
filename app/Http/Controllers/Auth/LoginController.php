<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use ThrottlesLogins;

    // Used by ThrottlesLogins
    protected function username(): string
    {
        return 'email';
    }

    public function login(Request $request): ?\Illuminate\Http\JsonResponse
    {
        // Validation
        $rules = [
           // Do not specify that the email is required to exist, as this exposes that a user is registered...
          'email' => 'required',
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

            /** @var User $user */
            $user = Auth::user();

            return response()->json([
              'user'  =>  $user, // <- we're sending the user info for frontend usage
              'token' =>  $user->createToken('yourAppName')->accessToken, // <- token is generated and sent back to the front end
          ]);
        } else {
            $this->incrementLoginAttempts($request);

            return response()->json('Unauthorized', 401);
        }
    }
}
