<?php

namespace App\Http\Controllers;

use App\WikiManager;
use Illuminate\Http\Request;

class WikiManagersController extends Controller
{
    public function getManagersOfWiki(Request $request): \Illuminate\Http\Response
    {
        $user = $request->user();

        $wikiId = $request->input('wiki');

        // TODO require user to be manager of the current wiki
        $test = WikiManager::where('user_id', $user->id)
      ->where('wiki_id', $wikiId)
      ->first();
        if (! $test) {
            abort(403);
        }

        $result = WikiManager::where('wiki_id', $wikiId)
      ->leftJoin('users', 'user_id', '=', 'users.id')
      ->select('users.*')
      ->get();

        $res['success'] = true;
        $res['data'] = $result;

        return response($res);
    }
}
