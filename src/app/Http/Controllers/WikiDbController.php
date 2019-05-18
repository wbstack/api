<?php

namespace App\Http\Controllers;

use App\WikiDb;
use Illuminate\Http\Request;

class WikiDbController extends Controller
{

    public function recordCreation( Request $request ){
        $this->validate($request, [
            'name' => 'required|unique:wiki_dbs',
            'user' => 'required',
            'password' => 'required',
            'version' => 'required',
        ]);

        $wikiDb = WikiDb::create([
            'name' => $request->input('name'),
            'user' => $request->input('user'),
            'password' => $request->input('password'),
            'version' => $request->input('version'),
        ]);

        $res['success'] = true;
        $res['message'] = 'Success!';
        $res['data'] = [
            'id' => $wikiDb->id,
            'name' => $wikiDb->name,
        ];
        return response($res);

    }

    public function countUnclaimed( Request $request ){
        $result = WikiDb::where( 'wiki_id', null )->count();
        $res['success'] = true;
        $res['data'] = $result;
        return response($res);
    }

}
