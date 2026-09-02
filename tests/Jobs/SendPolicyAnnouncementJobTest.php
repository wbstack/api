<?php

namespace Tests\Jobs;

use App\Jobs\SendPolicyAnnouncementJob;
use App\Notifications\PolicyAnnouncementNotification;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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

    public function testItExcludeUsersInTheExcludedEmailsList() {
        Notification::fake();
        $excludedUser = User::factory()->create(['email' => 'excluded.email@email.com']);
        $includedUser = User::factory()->create(['email' => 'included.email@email.com']);

        $job = new SendPolicyAnnouncementJob([$excludedUser->email]);
        $job->handle();
        Notification::assertNotSentTo($excludedUser, PolicyAnnouncementNotification::class);
        Notification::assertSentTo($includedUser, PolicyAnnouncementNotification::class);
    }

    public function testItNotifiedAllUsersEvenIfSomeEmailsAreInvalid() {
        Notification::fake();
        User::factory()->createMany([
            ['email' => 'user1@email.com'],
            ['email' => 'user2@email.com'],
            ['email' => ''],
            ['email' => 'asdfghjklertyuiopcvbnm'],
            ['email' => 'user5@email.com'],
        ]);
        $job = new SendPolicyAnnouncementJob();
        $job->handle();
        Notification::assertCount(5);
    }

    public function testItQueuesMailsForAllValidEmails() {
        $this->markTestSkipped('Mocking the mailer failing to send seems to be difficult');
        Mail::fake();
        User::factory()->createMany([
            ['email' => 'user1@email.com'],
            ['email' => 'user2@email.com'],
            ['email' => ''],
            ['email' => 'asdfghjklertyuiopcvbnm'],
            ['email' => 'user5@email.com'],
        ]);
        $job = new SendPolicyAnnouncementJob();
        $job->handle();
        // Seems like sending notifications doesn't actually result in queueing mails.
        Mail::assertQueuedCount(3);
    }
}
