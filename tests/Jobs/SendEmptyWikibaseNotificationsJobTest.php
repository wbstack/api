<?php

namespace Tests\Jobs;

use App\Jobs\SendEmptyWikibaseNotificationsJob;
use App\Jobs\UpdateWikiSiteStatsJob;
use App\Notifications\EmptyWikibaseNotification;
use App\User;
use App\Wiki;
use App\WikiManager;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendEmptyWikibaseNotificationsJobTest extends TestCase
{
    use RefreshDatabase;

    public function testEmptyWikibaseNotification_Success()
    {
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
        $user = User::factory()->create();
        $wiki = Wiki::factory()->create(['created_at' => '2022-12-31 16:00:00']);
        $manager = WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        $job = new SendEmptyWikibaseNotificationsJob();
        $job->handle();

        Notification::assertSentTo(
            $user->select('email')->get(),
            EmptyWikibaseNotification::class
        );
    }
}
