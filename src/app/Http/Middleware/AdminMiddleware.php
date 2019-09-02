<?php

namespace App\Http\Middleware;

use Closure;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        // Make sure there is an authed user
        if (! $user) {
            abort(403);
        }

        // And that user is me
        // TODO do something better here...?
        if ($user->email !== 'adamshorland@gmail.com') {
            abort(403);
        }

        return $next($request);
    }
}
