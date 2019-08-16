<?php

namespace App\Http\Controllers;

use App\Wiki;
use App\WikiDb;
use App\WikiManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WikisController extends Controller
{

    private function getAndRequireAuthedUser( Request $request ) {
      if(!$request->auth) {
        // This is a logic exception as the router / JWT middleware requires a user already
        throw new LogicException("Controller should not be run without auth");
      }
      return $request->auth;
    }

    public function count(){
        $res['success'] = true;
        // TODO only count active?
        $res['data'] = Wiki::count();
        return response($res);
    }

    public function getWikisOwnedByCurrentUser( Request $request ){
      $user = $this->getAndRequireAuthedUser( $request );
      $result = WikiManager::where('user_id', $user->id)
      ->leftJoin('wikis', 'wiki_id', '=', 'wikis.id')
      ->select('wikis.*')
      ->get();

      $res['success'] = true;
      $res['data'] = $result;
      return response($res);
    }

}
