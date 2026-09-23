<?php

namespace App\Http\Middleware;

use App\Wiki;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LimitWikiAccess {
    /**
     * Reject any incoming request unless the user is a manager of the
     * requested wiki. If the user is authorized, inject the wiki
     * object into the request context.
     */
    public function handle(Request $request, Closure $next): Response {
        $wiki = $request->route('wiki') ?? $request->input('wiki');

        if (!$wiki instanceof Wiki) {
            $request->merge(['wiki' => $wiki]);
            $validatedInput = $request->validate([
                'wiki' => ['required', 'integer'],
            ]);

            $wiki = Wiki::find($validatedInput['wiki']);

            if (!$wiki) {
                abort(404, 'No such wiki');
            }
        }

        $wikiManager = $wiki->wikiManagers()
            ->where('user_id', $request->user()?->id)
            ->first();

        if (!$wikiManager) {
            abort(403, "No permission to modify Wiki with ID {$wiki->id}");
        }

        $request->attributes->set('wiki', $wiki);

        return $next($request);
    }
}
