<?php

namespace Tests\Jobs;

use App\Jobs\SendPolicyAnnouncementJob;
use App\Notifications\PolicyAnnouncementNotification;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendPolicyAnnouncementJobTest extends TestCase {
    use RefreshDatabase;

    public function testThePolicyAnnouncementEmailToAllUsers() {
        Notification::fake();

        for ($i = 0; $i < 10; $i++) {
            User::factory()->create();
        }

        $users = User::all();

        $job = new SendPolicyAnnouncementJob();
        $job->handle();

        Notification::assertSentTo($users, PolicyAnnouncementNotification::class);
    }
}
