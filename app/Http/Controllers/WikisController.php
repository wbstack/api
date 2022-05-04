<?php

namespace App\Http\Controllers;

use App\Wiki;
use Illuminate\Http\Request;

class WikisController extends Controller
{
    public function getWikisOwnedByCurrentUser(Request $request): \Illuminate\Http\Response
    {
        return response(
          $request->user()
          ->managesWikis()
          ->get()
        );
    }
}
