<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{

  public function login(Request $request)
  {
      $rules = [
          'email' => 'required|exists:users',
          'password'  => 'required'
      ];
      $request->validate($rules);
      $data = [
          'email' => $request->get('email'),
          'password'  =>  $request->get('password'),
          //'is_active' => true
      ];
      if(Auth::attempt($data))
      {
          $user = Auth::user();
          // the $user->createToken('appName')->accessToken generates the JWT token that we can use
          return response()->json([
              'user'  =>  $user, // <- we're sending the user info for frontend usage
              'token' =>  $user->createToken('yourAppName')->accessToken, // <- token is generated and sent back to the front end
              // TODO SHIFT, old UI uses these, so keep adding them for now, but should use user key?
              'email' => $user->email,
              'isAdmin' => $user->isAdmin(),
          ]);
      }
      else
      {
          return response()->json('Unauthorized', 401);
      }
  }

}
