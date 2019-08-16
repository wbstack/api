<?php

namespace App\Http\Controllers;

use App\Wiki;
use App\WikiManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WikiManagersController extends Controller
{

    private function getAndRequireAuthedUser( Request $request ) {
      if(!$request->auth) {
        // This is a logic exception as the router / JWT middleware requires a user already
        throw new LogicException("Controller should not be run without auth");
      }
      return $request->auth;
    }

    public function getManagersOfWiki( Request $request ){
      $user = $this->getAndRequireAuthedUser( $request );

      $wikiId = $request->input('wiki');

      // TODO require user to be manager of the current wiki
      $test = WikiManager::where('user_id', $user->id)
      ->where('wiki_id', $wikiId)
      ->first();
      if(!$test) {
        $res['success'] = false;
        // TODO response code of not authorized
        return response($res);
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
