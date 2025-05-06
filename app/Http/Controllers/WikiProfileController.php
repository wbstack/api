<?php

namespace App\Http\Controllers;

use App\Helper\ProfileValidator;
use App\Rules\NonEmptyJsonRule;
use App\Wiki;
use App\WikiProfile;
use Illuminate\Http\Request;

class WikiProfileController extends Controller
{
    private $profileValidator;

    public function __construct(ProfileValidator $profileValidator)
    {
        $this->profileValidator = $profileValidator;
    }

    public function create(Request $request): \Illuminate\Http\JsonResponse
    {
        $validatedInput = $request->validate([
            'wiki' => ['required', 'integer'],
            'profile' => ['required', 'json', new NonEmptyJsonRule]
        ]);

        $wiki = Wiki::find($validatedInput['wiki']);
        if (!$wiki) {
            abort(404, 'No such wiki');
        }

        $rawProfile = json_decode($validatedInput['profile'], true);
        $profileValidator = $this->profileValidator->validate($rawProfile);
        $profileValidator->validateWithBag('post');

        $profile = WikiProfile::create(['wiki_id' => $wiki->id, ...$rawProfile]);
        return response()->json(['data' => $profile]);
    }
}
