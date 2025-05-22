<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\WikiManager;

class LimitWikiAccess
{
    /**
     * Reject any incoming request unless the user is a manager of the
     * requested wiki. If the user is authorized, inject the wiki
     * object into the request context.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $validatedInput = $request->validate([
            'wiki' => ['required', 'integer']
        ]);

        $wikiManager = WikiManager::where([
            'user_id' => $request->user()?->id,
            'wiki_id' => $validatedInput['wiki'],
        ])
        ->with('wiki')
        ->first();

        if (!$wikiManager) {
            abort(403);
        }

        if (!$wikiManager->wiki) {
            abort(404, 'No such wiki');
        }

        $request->attributes->set('wiki', $wikiManager->wiki);
        return $next($request);
    }
}
