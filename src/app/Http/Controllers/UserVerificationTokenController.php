<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use App\UserVerificationToken;
use App\Jobs\UesrVerificationTokenCreateAndSendJob;

class UserVerificationTokenController extends Controller
{
    public function verify(Request $request)
    {
        $this->validate($request, [
            'token' => 'required|exists:user_verification_tokens,token',
        ]);

        $token = UserVerificationToken::where('token', $request->input('token'))->firstOrFail();
        $user = User::where('id', $token->user_id)->firstOrFail();

        if ($user->verified) {
            // User already verified
            abort(403);
        }

        $user->verified = true;
        $user->save();
        $token->delete();

        $res['success'] = true;
        $res['message'] = 'Verified!';

        return response($res);
    }

    public function createAndSendForUser(Request $request)
    {
        $user = $request->user();

        if ($user->verified) {
            // User already verified
            abort(403);
        }

        // TODO why is this handle? Why not queue?
        ( new UesrVerificationTokenCreateAndSendJob($user) )->handle();
    }
}
