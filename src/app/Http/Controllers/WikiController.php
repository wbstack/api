<?php

namespace App\Http\Controllers;

use App\Wiki;
use App\WikiDb;
use App\WikiManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WikiController extends Controller
{

    private function getAndRequireAuthedUser( Request $request ) {
      if(!$request->auth) {
        // This is a logic exception as the router / JWT middleware requires a user already
        throw new LogicException("Controller should not be run without auth");
      }
      return $request->auth;
    }

    public function create( Request $request ){
        $user = $this->getAndRequireAuthedUser( $request );
        // TODO create the wiki with the user id as the owner...

        $this->validate($request, [
            'domain' => 'required|unique:wikis|regex:/^.+\.wiki\.opencura\.com$/',
            'sitename' => 'required',
        ]);

        $wikiDb = null;
        $dbAssignment = null;
        // TODO create with some sort of owner etc?
        DB::transaction( function() use ( $user, $request, &$wikiDb, &$dbAssignment ) {
            $wikiDb = Wiki::create([
                'sitename' => $request->input('sitename'),
                'domain' => $request->input('domain'),
            ]);

            $dbAssignment = DB::table('wiki_dbs')->where(['wiki_id'=>null])->limit(1)->update(['wiki_id' => $wikiDb->id]);

            $ownerAssignment = WikiManager::create([
              'user_id' => $user->id,
              'wiki_id' => $wikiDb->id,
            ]);
        } );

        $res['success'] = true;
        $res['message'] = 'Success!';
        $res['data'] = [
            'id' => $wikiDb->id,
            'name' => $wikiDb->name,
        ];
        return response($res);
    }

    public function count(){
        $res['success'] = true;
        // TODO only count active?
        $res['data'] = Wiki::count();
        return response($res);

    }

    public function list( Request $request ){
        $result = Wiki::all();

        $res['success'] = true;
        $res['data'] = $result;
        return response($res);
    }

    public function listWikisOwnedByCurrentUser( Request $request ){
      $user = $this->getAndRequireAuthedUser( $request );
      $result = WikiManager::where('user_id', $user->id)
      ->leftJoin('wikis', 'wiki_id', '=', 'wikis.id')
      ->select('wikis.*')
      ->get();

      $res['success'] = true;
      $res['data'] = $result;
      return response($res);
    }

    public function getWikiForDomain( Request $request ){
        $domain = $request->input('domain');

        // first, because we only expect 1 result, domain is unqiue
        // with, for eager loading of the wikiDb (in 1 query)
        $result = Wiki::where('domain', $domain)->with(['wikiDb'])->first();

        $res['success'] = true;
        $res['data'] = $result;
        return response($res);
    }

    public function getWikiDetailsForIdForOwner( Request $request ) {
      $user = $this->getAndRequireAuthedUser( $request );
      $wikiId = $request->input('wiki');

      // TODO general check to make sure current user can manage the wiki
      // this should probably be middle ware?
      // TODO only do 1 query where instead of 2?
      $test = WikiManager::where('user_id', $user->id)
      ->where('wiki_id', $wikiId)
      ->first();
      if(!$test) {
        $res['success'] = false;
        // TODO response code of not authorized
        return response($res);
      }

      $wiki = Wiki::where('id', $wikiId)
      ->with('wikiManagers')
      ->with('wikiDbVersion')
      ->first();

      $res['success'] = true;
      $res['data'] = $wiki;
      return response($res);

    }

}
