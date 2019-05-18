<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Validator;
use App\User;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class UserController extends BaseController
{
    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    private $request;
    /**
     * Create a new controller instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct(Request $request) {
        $this->request = $request;
    }

    /**
     * Register new user
     *
     * @param $request Request
     */
    public function register(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required'
        ]);

        //$hasher = app()->make('hash');
        $username = $request->input('username');
        $email = $request->input('email');
        //// TODO PASSWORD_DEFAULT  shouldnt be hardcoded everywhere...?
        //$password = password_hash( $request->input('password'), PASSWORD_DEFAULT );
        // Done the same hashing as in auth controller
        $password = Hash::make( $request->input('password') );
        $user = User::create([
            'username' => $username,
            'email' => $email,
            'password' => $password,
        ]);

        $res['success'] = true;
        $res['message'] = 'Register Successful!';
        $res['data'] = $this->convertUserForOutput( $user );
        return response($res);
    }

    public function self( Request $request ) {
        if ( isset( $request->auth ) ) {
            $res['success'] = true;
            // Filter what we give to the user
            $res['message'] = $this->convertUserForOutput( $request->auth );

            return response($res);
        }

        $res['success'] = false;
        $res['message'] = 'Cannot find user!';

        return response($res);
    }

    protected function convertUserForOutput ( User $user ) {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
        ];
    }

}
