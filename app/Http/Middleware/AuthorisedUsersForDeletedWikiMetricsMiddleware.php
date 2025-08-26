<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorisedUsersForDeletedWikiMetricsMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response {
        $user = $request->user();
        if (!is_null($user) && $user->is_admin === 1) {
            return $next($request);
        }

        return redirect()->route('login');
    }
}
