<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WikisController extends Controller {
    public function getWikisOwnedByCurrentUser(Request $request): Response {
        $wikis = $request->user()->managesWikis()->get();

        return response(
            [
                'wikis' => $wikis,
                'count' => count($wikis),
                'limit' => config('wbstack.wiki_max_per_user'),
            ]
        );
    }
}
