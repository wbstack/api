<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Config;

class LoginController extends Controller
{
    use ThrottlesLogins;

    // Used by ThrottlesLogins
    protected function username(): string
    {
        return 'email';
    }

    private static function getCookie(string $token): \Symfony\Component\HttpFoundation\Cookie
    {
        return Cookie::make(
            Config::get('auth.cookies.key'),
            $token,
            Config::get('auth.cookies.ttl_minutes'),
            Config::get('auth.cookies.path'),
            null,
            null,
            true,
            false,
            Config::get('auth.cookies.same_site'),
        );
    }

    private static function deleteCookie(): \Symfony\Component\HttpFoundation\Cookie
    {
        return Cookie::forget(
            Config::get('auth.cookies.key'),
            Config::get('auth.cookies.path'),
        );
    }

    public function getLogin(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    public function deleteLogin(Request $request)
    {
        return response()
            ->json()
            ->setStatusCode(204)
            ->withCookie($this->deleteCookie());
    }

    public function postLogin(Request $request): ?\Illuminate\Http\JsonResponse
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
        ];

        if (Auth::attempt($data)) {
            $this->clearLoginAttempts($request);

            /** @var User $user */
            $user = Auth::user();

            return response()->json([
              'user'  =>  $user, // <- we're sending the user info for frontend usage
            ])->withCookie(
                $this->getCookie($user->createToken('yourAppName')->accessToken)
            );
        } else {
            $this->incrementLoginAttempts($request);
            return response()->json('Unauthorized', 401);
        }
    }
}
