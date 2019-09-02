<?php

namespace App\Http\Controllers;

use App\Interest;
use Illuminate\Http\Request;

class InterestController extends Controller
{
    public function create(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|unique:interests',
        ]);

        $interest = Interest::create([
            'email' => $request->input('email'),
        ]);

        $res['success'] = true;
        $res['message'] = 'Registered!';

        return response($res);
    }
}
