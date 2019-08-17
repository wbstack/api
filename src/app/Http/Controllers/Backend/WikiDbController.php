<?php

namespace App\Http\Controllers\Backend;

use App\WikiDb;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WikiDbController extends Controller
{

    public function create( Request $request ){
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

}
