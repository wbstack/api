<?php

namespace App\Http\Controllers;

use App\Notifications\ContactNotification;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    /**
     * Handle a contact page request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
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
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        if (! isset($data['contactDetails'])) {
            $data['contactDetails'] = '';
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
            'recaptcha'      => ['string', 'required', 'captcha'],
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
