<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\WikiManager;

class LimitWikiAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userHasAccess = WikiManager::where([
            'user_id' => $request->user()?->id,
            'wiki_id' => $request->input('wiki'),
        ])->exists();

        if (!$userHasAccess) {
            abort(403);
        }

        return $next($request);
    }
}
