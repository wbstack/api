<?php

namespace App\Http\Controllers;

use App\Notifications\ContactNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

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

        logger()->info($request);
        logger()->info($validator->fails() ? 'request data FAILED validation':'request data PASSED validation');
        logger()->info($validator->failed());
        logger()->info($validator->errors()->messages());

        logger()->info($validator->validated());
        
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
        if (! isset($data['contactDetails'])) {
            $data['contactDetails'] = ''; // could we skip this using some feature of the validator
        }

        $validSubjects = [
            'general-question',
            'feature-request',
            'report-a-problem',
            'give-feedback',
            'other',
        ];

        $validation = [
            'subject'        => ['string', 'required', 'max:300', Rule::in($validSubjects)],
            'name'           => ['string', 'required', 'max:300'],
            'message'        => ['string', 'required', 'max:10000'],
            'recaptcha'      => ['string', 'required', 'recaptcha'],
            'contactDetails' => ['string', 'nullable', 'max:300'],
        ];

        // XXX: for phpunit dont validate captcha when requested....
        // TODO this should be mocked in the test instead
        if (getenv('PHPUNIT_RECAPTCHA_CHECK') == '0') {
            unset($validation['recaptcha']);
        }

        return Validator::make($data, $validation);
    }
}
