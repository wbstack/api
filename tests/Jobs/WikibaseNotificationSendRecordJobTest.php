<?php

namespace Tests\Jobs;

use App\Jobs\WikibaseNotificationSendRecordJob;
use App\Notifications\EmptyWikibaseNotification;
use App\User;
use App\Wiki;
use App\WikiManager;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WikibaseNotificationSendRecordJobTest extends TestCase
{
    public function testEmptyWikibaseNotification_Success()
    {
//        Queue::fake();

        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create(['created_at' => '2022-12-31 16:00:00']);
        $manager = WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        Bus::fake();

        Bus::assertDispatched(WikibaseNotificationSendRecordJob::class);
//
//        Queue::assertPushed( WikibaseNotificationSendRecordJob::class, function ($job) {
//
//        });
//        $mockJob = $this->createMock(Job::class);
//        $mockJob->expects($this->never())
//                ->method('fail')
//                ->withAnyParameters();
//        $job = new WikibaseNotificationSendRecordJob();
//        $job->setJob($mockJob);
//        $job->handle();
//
//        Notification::fake();
//
//        Notification::assertSentTo([$user], EmptyWikibaseNotification::class);
    }
}
