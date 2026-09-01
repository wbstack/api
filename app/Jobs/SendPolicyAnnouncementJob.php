<?php

namespace App\Jobs;

use App\Notifications\PolicyAnnouncementNotification;
use App\User;
use Illuminate\Support\Facades\Notification;

/**
 * WARNING: This job is NOT idempotent. DO NOT RUN IT MULTIPLE TIMES.
 * There is also no error handling or mechanism for recording which users have been sent an email.
 *
 * This has created an issue on production where a user with an empty string as an email address
 * (due to a request to remove PII) caused Notification::send() to throw an error and the remaining
 * emails to not be sent.
 *
 * DO NOT REPEAT THIS PATTERN FOR OTHER JOBS
 */
class SendPolicyAnnouncementJob extends Job {
    public function __construct(private readonly array $excludedEmails = []) {}

    public function handle() {
        $users = User::query()->whereNotNull('email')->whereNotIn('email', $this->excludedEmails)->get();

        Notification::send($users, new PolicyAnnouncementNotification());
    }
}
