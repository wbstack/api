<?php

namespace App\Http\Controllers;

use App\Notifications\ComplaintNotificationExternal;
use App\Notifications\ComplaintNotification;
use App\Rules\ReCaptchaValidation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use App\ComplaintRecord;

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

        $complaintRecord = new ComplaintRecord;
        $complaintRecord->name = $validated['name'];
        $complaintRecord->mail_address = $validated['mailAddress'];
        $complaintRecord->reason = $validated['reason'];
        $complaintRecord->offending_urls = $validated['offendingUrls'];
        $complaintRecord->save();

        if (! empty($validated['mailAddress'])) {
            Notification::route('mail', [
                $validated['mailAddress'],
            ])->notify(
                new ComplaintNotificationExternal(
                    $complaintRecord->offending_urls,
                    $complaintRecord->reason,
                    $complaintRecord->name,
                    $complaintRecord->mail_address,
                )
            );
        }

        Notification::route('mail', [
            config('app.complaint-mail-recipient'),
        ])->notify(
    new ComplaintNotification(
            $complaintRecord->offending_urls,
            $complaintRecord->reason,
            $complaintRecord->name,
            $complaintRecord->mail_address,
            )
        );

        $complaintRecord->markAsDispatched();
        $complaintRecord->save();

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
                    !empty($data['mailAddress']),
                    ['email:rfc']
                ),
            ],
        ];

        return Validator::make($data, $validation);
    }
}
