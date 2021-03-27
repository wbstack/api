<?php

namespace App\Http\Controllers;

use App\Jobs\UserVerificationCreateTokenAndSendJob;
use App\User;
use App\UserVerificationToken;
use Illuminate\Http\Request;

/**
 * Verification of user emails.
 */
class UserVerificationTokenController extends Controller
{
    public function verify(Request $request)
    {
        $request->validate([
            'token' => 'required|exists:user_verification_tokens,token',
        ]);

        $token = UserVerificationToken::where('token', $request->input('token'))->firstOrFail();
        $user = User::where('id', $token->user_id)->firstOrFail();

        if ($user->verified) {
            // User already verified
            $res['success'] = true;
            $res['message'] = 'Already Verified!';

            return response($res);
        }

        $user->verified = 1;
        $user->save();
        $token->delete();

        $res['success'] = true;
        $res['message'] = 'Successfully Verified!';

        return response($res);
    }

    public function createAndSendForUser(Request $request)
    {
        $user = $request->user();

        if ($user->verified) {
            $res['success'] = true;
            $res['message'] = 'Already verified';

            return response($res);
        }

        // TODO why is this handle? Why not queue?
        (UserVerificationCreateTokenAndSendJob::newForReverification($user))->handle();

        $res['success'] = true;
        $res['message'] = 'Email sent!';

        return response($res);
    }
}
