<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * The purpose of RedirectIfAuthenticated is to keep an already authenticated user
 * from reaching the login or registration routes/views since they're already logged in.
 */
class RedirectIfAuthenticated {
    /**
     * Handle an incoming request.
     *
     * @param  string|null  $guard
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return response()->json('Endpoint not needed', 400);
            }
        }

        return $next($request);
    }
}
