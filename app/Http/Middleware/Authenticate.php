<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    public function handle($request, Closure $next, ...$guards)
    {
        try {
            $this->authenticate($request, $guards);
        } catch (AuthenticationException $e) {
            // The "Unauthenticated." message is relied on by the UI to detect logged out state and cleanup its data
            // If this changes then the UI also needs to change..
            return response()->json([
                'error' => 'Unauthenticated.',
            ], 401);
        }

        return $next($request);
    }
}
