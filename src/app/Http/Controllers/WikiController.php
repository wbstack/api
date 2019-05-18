<?php

namespace App\Http\Controllers;

use App\Wiki;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WikiController extends Controller
{

    public function create( Request $request ){
        $this->validate($request, [
            'subdomain' => 'required|unique:wikis',
            'sitename' => 'required',
            // TODO this bottom one isn't actually required?
            'metanamespace' => 'required',
        ]);

        $wikiDb = null;
        $dbAssignment = null;
        // TODO create with the correct owner
        DB::transaction( function() use ( $request, &$wikiDb, &$dbAssignment ) {
            $wikiDb = Wiki::create([
                'subdomain' => $request->input('subdomain'),
                'sitename' => $request->input('sitename'),
                'metanamespace' => $request->input('metanamespace'),
            ]);

            $dbAssignment = DB::table('wiki_dbs')->where(['wiki_id'=>null])->limit(1)->update(['wiki_id' => $wikiDb->id]);
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

        foreach ( $result as &$wiki ) {
            // TODO do not hardcode this...
            $wiki['homesrc'] = '//' . $wiki['subdomain'] . '.mw.ww.10.0.75.2.xip.io:1999';
        }

        $res['success'] = true;
        $res['data'] = $result;
        return response($res);
    }

    public function getForUser( Request $request ) {
        // TODO actually select for a given user
        return $this->list( $request );
    }

    public function getWikiForSubdomain( Request $request ){
        $subdomain = $request->input('subdomain');

        // first, because we only expect 1 result, subdomain is unqiue
        // with, for eager loading of the wikiDb (in 1 query)
        $result = Wiki::where('subdomain', $subdomain)->with(['wikiDb'])->first();

        $res['success'] = true;
        $res['data'] = $result;
        return response($res);
    }

}