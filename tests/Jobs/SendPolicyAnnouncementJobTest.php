<?php

namespace Tests\Jobs;

use App\Jobs\SendPolicyAnnouncementJob;
use App\Notifications\PolicyAnnouncementNotification;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mime\Exception\RfcComplianceException;
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

    public function testItNotifiedAllUsersEvenIfMailerThrowsRfcComplianceException() {
        // This test specifically simualtes the situation we saw in T432211#12270805
        Notification::shouldReceive('send')
            ->once()
            ->andThrow(new RfcComplianceException());
        Notification::shouldReceive('send')
            ->atLeast()
            ->times(3);

        User::factory()->createMany([
            ['email' => 'asdfghjklertyuiopcvbnm'],
            ['email' => 'user1@email.com'],
            ['email' => 'user2@email.com'],
            ['email' => ''],
            ['email' => 'user5@email.com'],
        ]);
        $job = new SendPolicyAnnouncementJob();
        $job->handle();
    }
}
