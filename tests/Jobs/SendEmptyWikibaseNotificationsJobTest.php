<?php

namespace Tests\Jobs;

use App\Jobs\SendEmptyWikibaseNotificationsJob;
use App\Notifications\EmptyWikibaseNotification;
use App\User;
use App\Wiki;
use App\WikibaseNotificationSentRecord;
use App\WikiLifecycleEvents;
use App\WikiManager;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use function Amp\Promise\first;

class SendEmptyWikibaseNotificationsJobTest extends TestCase
{
    use RefreshDatabase;

    // the job does not fail in general
    public function testEmptyWikibaseNotifications_Success()
    {
        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())
                ->method('fail')
                ->withAnyParameters();
        $job = new SendEmptyWikibaseNotificationsJob();
        $job->setJob($mockJob);
        $job->handle();
    }

    // empty wikis, that are older than 30 days, trigger a notification
    public function testEmptyWikibaseNotifications_SendNotification()
    {
        Notification::fake();
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create(['created_at' => '2022-12-31 16:00:00']);
        $manager = WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);
        $wiki->wikiLifecycleEvents()->updateOrCreate(['first_edited' => null]);

        $job = new SendEmptyWikibaseNotificationsJob();
        $job->handle();

        Notification::assertSentTo(
            $user->select('email')->get(),
            EmptyWikibaseNotification::class
        );
    }

    // non-empty wikis which are older than 30 days do not trigger notifications
    public function testEmptyWikibaseNotifications_ActiveWiki()
    {
        Notification::fake();
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create(['created_at' => '2022-12-31 16:00:00']);
        $manager = WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        WikiLifecycleEvents::factory()->create(['wiki_id' => $wiki->id, 'first_edited' => '2023-01-01 16:00:00']);

        $job = new SendEmptyWikibaseNotificationsJob();
        $job->handle();

        Notification::assertNothingSent();
    }

    // notifications do not get sent again
    public function testEmptyWikibaseNotifications_EmptyNotificationReceived()
    {
        Notification::fake();
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create(['created_at' => '2022-12-31 16:00:00']);
        $manager = WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        WikibaseNotificationSentRecord::factory()->create(['wiki_id' => $wiki->id, 'notification_type' => 'empty_wikibase_notification']);

        $job = new SendEmptyWikibaseNotificationsJob();
        $job->handle();

        Notification::assertNothingSent();
    }
}
