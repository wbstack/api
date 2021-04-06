<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\OauthAccessTokens;
use App\User;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Lcobucci\JWT\Parser;
class LoginController extends Controller
{
    use ThrottlesLogins;

    // Used by ThrottlesLogins
    protected function username()
    {
        return 'email';
    }

    public function login(Request $request)
    {
        // Validation
        $rules = [
           // Do not specify that the email is required to exist, as this exposes that a user is registered...
          'email' => 'required',
          'password'  => 'required',
      ];
        $request->validate($rules);

        // Throttle
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        // Try login
        $data = [
          'email' => $request->get('email'),
          'password'  =>  $request->get('password'),
          //'is_active' => true
      ];
        if (Auth::attempt($data)) {
            $this->clearLoginAttempts($request);

            /** @var User $user */
            $user = Auth::user();

            return response()->json([
              'user'  =>  $user, // <- we're sending the user info for frontend usage
              'token' =>  $user->createToken('yourAppName')->accessToken, // <- token is generated and sent back to the front end
          ]);
        } else {
            $this->incrementLoginAttempts($request);

            return response()->json('Unauthorized', 401);
        }
    }

    public function logout(Request $request)
    {
        $token = $request->bearerToken();
        $tokenId = (new Parser())->parse($token)->getClaims()['jti']->getValue();
        $res = OauthAccessTokens::where('id',$tokenId)->delete();
        if ($res) {
            return response(['success'=>true]);
        } else {
            return response(['success'=>false,'error'=>'ops ! something went wrong']);
        }
    }

    // A best practice login method with the access_token + refresh_token to get the full benifit of 
    // laravel passport
    // This return access_token + refresh_token as a response
    /**
     * grant_type    = password
     * client_id     = [passport client_id]
     * client_secret = [passport client_secret]
     * username      = a@a.a
     * password      = a
     */
    // This refresh the life span of a token and return access_token + refresh_token as a response
    /**
     * grant_type    = refresh_token
     * client_id     = [passport client_id]
     * client_secret = [passport client_secret]
     * refresh_token = [refresh_token] 
     */
    // public function login_V2(Request $request)
    // {
    //     $http = new \GuzzleHttp\Client(['verify' => false]);

    //     if($request->grant_type === 'refresh_token'){
    //         $params = [
    //             'grant_type'    => $request->grant_type,
    //             'client_id'     => $request->client_id,
    //             'client_secret' => $request->client_secret,
    //             'refresh_token' => $request->refresh_token,
    //             'scope'         => '',
    //         ];
    //     }else {
    //         $params = [
    //             'grant_type'    => $request->grant_type,
    //             'client_id'     => $request->client_id,
    //             'client_secret' => $request->client_secret,
    //             'username'      => $request->username,
    //             'password'      => $request->password,
    //             'scope'         => '',
    //         ];
    //         $res['user'] = User::where('email',request('username'))->first();
    //     }

    //     try{
    //         // i am using the container url to make requests secure inside the container local network itself
    //         // so no need to change this 
    //         $domain = "http://api";
    //         $response = $http->post($domain.'/oauth/token', [
    //         'form_params' => $params
    //         ]);
    //     }
    //     catch(RequestException $e){
    //         return response(['message'=>'refresh token expired','status'=>505],505);
    //         //return response($e->getMessage());
    //     }
    //     $res['tokens'] = json_decode((string) $response->getBody(), true);

    //     return response($res);
    // }

    // A best practice logout method with the revoke logic and the purge with crontab task
    // see app/Console/Kernel@schedule but not implemented because you didn't request it
    // public function logout_V2(Request $request)
    // {
    //     $tokenRepository = app('Laravel\Passport\TokenRepository');
    //     $refreshTokenRepository = app('Laravel\Passport\RefreshTokenRepository');
    //     $token = $request->bearerToken();
    //     $tokenId = (new Parser())->parse($token)->getClaims()['jti']->getValue();
    //     // Revoke an access token...
    //     $tokenRepository->revokeAccessToken($tokenId);
    //     // Revoke all of the token's refresh tokens...
    //     $refreshTokenRepository->revokeRefreshTokensByAccessTokenId($tokenId);

    // }
}
