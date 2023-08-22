<?php

namespace App\Http\Controllers;

use App\Notifications\ContactNotification;
use App\Rules\ReCaptchaValidation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    /**
     * @var \App\Rules\ReCaptchaValidation
     */
    protected $recaptchaValidation;

    public function __construct(ReCaptchaValidation $recaptchaValidation) {
        $this->recaptchaValidation = $recaptchaValidation;
    }

    /**
     * Handle a contact page request for the application.
     *
     * @param \Illuminate\Http\Request  $request
     */
    public function sendMessage(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            $failed = $validator->failed();

            if (isset($failed['recaptcha'])) {
                abort(401);
            } else {
                abort(400);
            }
        }

        $validated = $validator->safe();

        Notification::route('mail', [
            config('app.contact-mail-recipient'),
        ])->notify(
            new ContactNotification(
                $validated['name'],
                $validated['subject'],
                $validated['message'],
                $validated['contactDetails'],
            )
        );

        return response()->json('Success', 200);
    }

    /**
     * Get a validator for an incoming contact page request.
     */
    protected function validator(array $data): \Illuminate\Validation\Validator
    {
        $validSubjects = [
            'general-question',
            'feature-request',
            'report-a-problem',
            'give-feedback',
            'other',
        ];

        $validation = [
            'recaptcha'      => ['required', 'string', 'bail', $this->recaptchaValidation],
            'subject'        => ['required', 'string', 'max:300', Rule::in($validSubjects)],
            'name'           => ['required', 'string', 'max:300'],
            'message'        => ['required', 'string', 'max:10000'],
            'contactDetails' => ['string', 'nullable', 'max:300'],
        ];

        return Validator::make($data, $validation);
    }
}
