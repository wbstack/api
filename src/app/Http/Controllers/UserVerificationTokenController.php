<?php

namespace App\Http\Controllers;

use App\User;
use App\UserVerificationToken;
use Illuminate\Http\Request;

class UserVerificationTokenController extends Controller
{

    public function verify( Request $request ){
        $this->validate($request, [
            'token' => 'required|exists:user_verification_tokens,token',
        ]);

        $token = UserVerificationToken::where('token', $request->input('token'))->first();
        $user = User::where('id', $token->user_id)->first();
        $user->verified = true;
        $user->save();
        $token->delete();

        $res['success'] = true;
        $res['message'] = 'Verified!';
        return response($res);

    }

}
