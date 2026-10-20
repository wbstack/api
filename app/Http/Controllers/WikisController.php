<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WikisController extends Controller {
    public function getWikisOwnedByCurrentUser(Request $request): Response {
        // TODO: Is augmenting the data here understandable
        // I believe using withExists is quite efficient

        $wikis = $request
            ->user()
            ->managesWikis()
            ->withExists('pendingReview as has_pending_review')
            ->get();

        return response(
            [
                'wikis' => $wikis,
                'count' => count($wikis),
                'limit' => config('wbstack.wiki_max_per_user'),
            ]
        );
    }
}
