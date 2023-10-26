<?php

namespace Tests\Jobs;

use App\Jobs\SendEmptyWikibaseNotificationsJob;
use App\Notifications\EmptyWikibaseNotification;
use App\User;
use App\Wiki;
use App\WikiManager;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendEmptyWikibaseNotificationsJobTest extends TestCase
{
    use DatabaseTransactions;

    public function testEmptyWikibaseNotification_Success()
    {
//        Queue::fake();
//
//        $user = User::factory()->create(['verified' => true]);
//        $wiki = Wiki::factory()->create(['created_at' => '2022-12-31 16:00:00']);
//        $manager = WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);
//
//        Bus::fake();
//
//        Bus::assertDispatched(SendEmptyWikibaseNotificationsJob::class);
        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())
                ->method('fail')
                ->withAnyParameters();
        $job = new SendEmptyWikibaseNotificationsJob();
        $job->setJob($mockJob);
        $job->handle();
    }

    public function testEmptyWikibaseNotifications_SendNotification()
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'fooandbar@example.com']);
        $wiki = Wiki::factory()->create(['created_at' => '2022-12-31 16:00:00']);
        $manager = WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        $job = new SendEmptyWikibaseNotificationsJob();
        $job->handle();

        Notification::assertSentTo(
            $user,
            function (EmptyWikibaseNotification $notification) use ($user){
                return true;
            }
        );
    }
}
