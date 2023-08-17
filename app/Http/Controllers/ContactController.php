<?php

namespace App\Http\Controllers;

use App\Notifications\ContactNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use App\Rules\RecaptchaValidation;

class ContactController extends Controller
{
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
            'recaptcha'      => ['required', 'string', 'bail', new RecaptchaValidation],
            'subject'        => ['required', 'string', 'max:300', Rule::in($validSubjects)],
            'name'           => ['required', 'string', 'max:300'],
            'message'        => ['required', 'string', 'max:10000'],
            'contactDetails' => ['string', 'nullable', 'max:300'],
        ];

        // TODO this should be mocked in the test instead
        if (app()->environment('testing')) {
            if (getenv('PHPUNIT_RECAPTCHA_CHECK') === '0') {
                unset($validation['recaptcha']);            
            }
        }

        return Validator::make($data, $validation);
    }
}
