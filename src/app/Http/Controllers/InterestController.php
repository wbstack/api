<?php

namespace App\Http\Controllers;

use App\Interest;
use Illuminate\Http\Request;

class InterestController extends Controller
{

    public function recordCreation( Request $request ){
        $this->validate($request, [
            'email' => 'required|email|unique:interests',
        ]);
        $email = $request->input('email');

        $test = Interest::where('email', $email)->first();
        if($test) {
          // Interest already registered
          $res['success'] = false;
          $res['message'] = 'Email already exists.';
          return response($res);
        }

        $interest = Interest::create([
            'email' => $email,
        ]);

        $res['success'] = true;
        $res['message'] = 'Registered!';
        return response($res);

    }

}
