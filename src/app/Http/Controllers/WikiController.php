<?php

namespace App\Http\Controllers;

use App\Wiki;
use App\WikiDb;
use App\WikiManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WikiController extends Controller
{

    public function create( Request $request ){
        // TODO create the wiki with the user id as the owner...

        $this->validate($request, [
            'domain' => 'required|unique:wikis|regex:/^.+\.wiki\.opencura\.com$/',
            'sitename' => 'required',
        ]);

        $wiki = null;
        $dbAssignment = null;
        // TODO create with some sort of owner etc?
        DB::transaction( function() use ( $user, $request, &$wiki, &$dbAssignment ) {
            $readyDbs = WikiDb::where( 'wiki_id', null )->count();
            if($readyDbs == 0) {
              abort(503, 'No databases ready');
            }

            $wiki = Wiki::create([
                'sitename' => $request->input('sitename'),
                'domain' => $request->input('domain'),
            ]);

            $dbAssignment = DB::table('wiki_dbs')->where(['wiki_id'=>null])->limit(1)->update(['wiki_id' => $wiki->id]);
            if(!$dbAssignment) {
              abort(503, 'Database ready, but failed to assign');
            }

            $ownerAssignment = WikiManager::create([
              'user_id' => $user->id,
              'wiki_id' => $wiki->id,
            ]);
        } );

        $res['success'] = true;
        $res['message'] = 'Success!';
        $res['data'] = [
            'id' => $wiki->id,
            'name' => $wiki->name,
        ];
        return response($res);
    }

    // TODO should this just be get wiki?
    public function getWikiDetailsForIdForOwner( Request $request ) {
      $wikiId = $request->input('wiki');

      // TODO general check to make sure current user can manage the wiki
      // this should probably be middle ware?
      // TODO only do 1 query where instead of 2?
      $test = WikiManager::where('user_id', $user->id)
      ->where('wiki_id', $wikiId)
      ->first();
      if(!$test) {
        abort(403);
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
