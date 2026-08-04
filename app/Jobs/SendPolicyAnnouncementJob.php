<?php

namespace App\Jobs;

use App\Notifications\PolicyAnnouncementNotification;
use App\User;
use Illuminate\Support\Facades\Notification;

class SendPolicyAnnouncementJob extends Job {
    public function handle() {
        $users = User::query()->whereNotNull('email')->get();

        Notification::send($users, new PolicyAnnouncementNotification());
    }
}
