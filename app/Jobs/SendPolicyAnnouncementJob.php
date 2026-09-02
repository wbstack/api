<?php

namespace App\Jobs;

use App\Notifications\PolicyAnnouncementNotification;
use App\User;
use Illuminate\Support\Facades\Log;

/**
 * WARNING: This job is NOT idempotent. DO NOT RUN IT MULTIPLE TIMES.
 * There is also no error handling or mechanism for recording which users have been sent an email.
 *
 * This has created an issue on production where a user with a weird string as an email address
 * (due to a request to remove PII) caused Notification::send() to throw an error and the remaining
 * emails to not be sent.
 *
 * DO NOT REPEAT THIS PATTERN FOR OTHER JOBS
 */
class SendPolicyAnnouncementJob extends Job {
    public function __construct(private readonly array $excludedEmails = []) {}

    public function handle(): void {
        $users = User::query()->whereNotNull('email')->whereNotIn('email', $this->excludedEmails)->get();

        $users->each(function (User $user) {
            try {
                $user->notify(new PolicyAnnouncementNotification());
                Log::info('PolicyAnnouncementNotification sent successfully', [$user->id, $user->email]);
            } catch (\Exception $exception) {
                Log::error($exception->getMessage(), [$user->id, $user->email]);
            }
        });
    }
}
