<?php

namespace App\Http\Controllers;

use App\Interest;
use Illuminate\Http\Request;

class InterestController extends Controller
{

    public function recordCreation( Request $request ){
        $this->validate($request, [
            'email' => 'required|email|unique:interest',
        ]);
        $email = $request->input('email');

        $test = Interest::where('email', $email)->first();
        if($test) {
          // Interest already registered
          $res['success'] = false;
          $res['message'] = 'Your interest has already been registered.';
          return response($res);
        }

        $interest = Interest::create([
            'email' => $email,
        ]);

        $res['success'] = true;
        $res['message'] = 'Interest registered!';
        return response($res);

    }

}
