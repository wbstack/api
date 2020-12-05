<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * The purpose of RedirectIfAuthenticated is to keep an already authenticated user
 * from reaching the login or registration routes/views since they're already logged in.
 */
class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->check()) {
            return response()->json('Endpoint not needed', 400);
            //return redirect('/home');
        }

        return $next($request);
    }
}
