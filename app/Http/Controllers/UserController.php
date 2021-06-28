<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

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
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function getSelf(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $res['success'] = true;
            // Filter what we give to the user
            $res['data'] = $this->convertUserForOutput($user);

            return response($res);
        }

        abort(404);

        return response();
    }

    // TODO why is this needed?
    // TODO the model used by the frontend stuff should just not have the password...
    /**
     * @return (int|string)[]
     *
     * @psalm-return array{id: int, email: string, verified: int}
     */
    protected function convertUserForOutput(User $user): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'verified' => $user->verified,
        ];
    }
}
