<?php

namespace App\Http\Controllers;

use App\Wiki;
use App\WikiDb;
use App\WikiManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WikisController extends Controller
{
    public function count()
    {
        $res['success'] = true;
        // TODO only count active?
        $res['data'] = Wiki::count();

        return response($res);
    }

    public function getWikisOwnedByCurrentUser(Request $request)
    {
        $user = $request->user();
        $result = WikiManager::where('user_id', $user->id)
      ->leftJoin('wikis', 'wiki_id', '=', 'wikis.id')
      ->select('wikis.*')
      ->get();

        $res['success'] = true;
        $res['data'] = $result;

        return response($res);
    }
}
