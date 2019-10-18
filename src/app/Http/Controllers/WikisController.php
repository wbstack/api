<?php

namespace App\Http\Controllers;

use App\Wiki;
use Illuminate\Http\Request;

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
        // TODO FIXME, right now this returns alll of the details of the wiki managers :/
        // which it should not do FIXME BEFORE RELEASE...
        return response(
          $request->user()
          ->managesWikis()
          ->with('wikiManagers')
          ->with('wikiDbVersion')
          ->get()
        );
    }
}
