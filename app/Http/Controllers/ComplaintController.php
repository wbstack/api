<?php

namespace App\Http\Controllers;

use App\Notifications\ExternalComplaintNotification;
use App\Notifications\ComplaintNotification;
use App\Rules\ReCaptchaValidation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class ComplaintController extends Controller
{
    /**
     * @var \App\Rules\ReCaptchaValidation
     */
    protected $recaptchaValidation;

    public function __construct(ReCaptchaValidation $recaptchaValidation) {
        $this->recaptchaValidation = $recaptchaValidation;
    }

    /**
     * Handle a complaint report page request for the application.
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
            config('app.complaint-mail-recipient'),
        ])->notify(
            new ComplaintNotification(
                $validated['offendingUrls'],
                $validated['reason'],
                $validated['name'],
                $validated['mailAddress'],
            )
        );

        if (! empty($validated['mailAddress'])) {
            Notification::route('mail', [
                $validated['mailAddress'],
            ])->notify(
                new ExternalComplaintNotification(
                    $validated['offendingUrls'],
                    $validated['reason'],
                    $validated['name'],
                    $validated['mailAddress'],
                )
            );
        }

        return response()->json('Success', 200);
    }

    /**
     * Get a validator for an incoming complaint report page request.
     */
    protected function validator(array $data): \Illuminate\Validation\Validator
    {
        $data['name'] = $data['name'] ?? '';
        $data['mailAddress'] = $data['mailAddress'] ?? '';

        $validation = [
            'recaptcha'      => ['required', 'string', 'bail', $this->recaptchaValidation],
            'name'           => ['nullable', 'string', 'max:300'],
            'reason'         => ['required', 'string', 'max:10000'],
            'offendingUrls'  => ['required', 'string', 'max:10000'],

            'mailAddress'    => [
                'nullable',
                'max:300',
                Rule::when(
                    !empty($mailAddress), 
                    ['email:rfc,dns']
                ),
            ],
        ];

        return Validator::make($data, $validation);
    }
}
