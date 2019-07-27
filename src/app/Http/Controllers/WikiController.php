<?php

namespace App\Http\Controllers;

use App\Wiki;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WikiController extends Controller
{

    public function create( Request $request ){
        $this->validate($request, [
            'domain' => 'required|unique:wikis|regex:/^.+\.wiki\.opencura\.com$/',
            'sitename' => 'required',
        ]);

        $wikiDb = null;
        $dbAssignment = null;
        // TODO create with some sort of owner etc?
        DB::transaction( function() use ( $request, &$wikiDb, &$dbAssignment ) {
            $wikiDb = Wiki::create([
                'sitename' => $request->input('sitename'),
                'domain' => $request->input('domain'),
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

        $res['success'] = true;
        $res['data'] = $result;
        return response($res);
    }

    public function getForUser( Request $request ) {
        // TODO actually select for a given user
        return $this->list( $request );
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

}
