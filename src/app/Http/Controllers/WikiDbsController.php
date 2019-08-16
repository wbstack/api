<?php

namespace App\Http\Controllers;

use App\WikiDb;
use Illuminate\Http\Request;

class WikiDbsController extends Controller
{

    public function countUnclaimed( Request $request ){
        $result = WikiDb::where( 'wiki_id', null )->count();
        $res['success'] = true;
        $res['data'] = $result;
        return response($res);
    }

}
