<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ContactController extends Controller
{
    protected function submitMessageResponse(): \Illuminate\Http\JsonResponse
    {
        return response()->json('Success', 200);
    }

    protected function submitMessageFailedResponse(): \Illuminate\Http\JsonResponse
    {
        return response()->json('Error', 400);
    }
}
