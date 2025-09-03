<?php

namespace App\Http\Controllers;

use App\ComplaintRecord;
use App\Notifications\ComplaintNotification;
use App\Notifications\ComplaintNotificationExternal;
use App\Rules\ReCaptchaValidation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ComplaintController extends Controller {
    /**
     * @var \App\Rules\ReCaptchaValidation
     */
    protected $recaptchaValidation;

    public function __construct(ReCaptchaValidation $recaptchaValidation) {
        $this->recaptchaValidation = $recaptchaValidation;
    }

    /**
     * Handle a complaint report page request for the application.
     */
    public function sendMessage(Request $request): \Illuminate\Http\JsonResponse {
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
        $complaintRecord->mail_address = $validated['email'];
        $complaintRecord->reason = $validated['message'];
        $complaintRecord->offending_urls = $validated['url'];
        $complaintRecord->save();

        if (!empty($complaintRecord->mail_address)) {
            Notification::route('mail', [
                $complaintRecord->mail_address,
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
    protected function validator(array $data): \Illuminate\Validation\Validator {
        $data['name'] = $data['name'] ?? '';
        $data['email'] = $data['email'] ?? '';

        $validation = [
            'recaptcha' => ['required', 'string', 'bail', $this->recaptchaValidation],
            'name' => ['nullable', 'string', 'max:300'],
            'message' => ['required', 'string', 'max:1000'],
            'url' => ['required', 'string', 'max:1000'],

            'email' => [
                'nullable',
                'max:300',
                Rule::when(
                    !empty($data['email']),
                    ['email:rfc']
                ),
            ],
        ];

        return Validator::make($data, $validation);
    }
}
