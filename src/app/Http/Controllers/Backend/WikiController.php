<?php

namespace App\Http\Controllers\Backend;

use App\Wiki;
use App\WikiDb;
use App\WikiManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WikiController extends Controller
{

      public function getWikiForDomain( Request $request ){
          $domain = $request->input('domain');

          // first, because we only expect 1 result, domain is unqiue
          // with, for eager loading of the wikiDb (in 1 query)
          $result = Wiki::where('domain', $domain)->with(['wikiDb'])->first();

          // TODO should this be accessible to everyone? Probably not!!!!
          // SECURITY

          $res['success'] = true;
          $res['data'] = $result;
          return response($res);
      }

}
