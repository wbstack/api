<?php

namespace Tests\Jobs;

use App\Jobs\SendEmptyWikiNotificationsJob;
use App\Notifications\EmptyWikiNotification;
use App\User;
use App\Wiki;
use App\WikiNotificationSentRecord;
use App\WikiLifecycleEvents;
use App\WikiManager;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Carbon\Carbon;

class SendEmptyWikiNotificationsJobTest extends TestCase
{
    use RefreshDatabase;

    // the job does not fail in general
    public function testEmptyWikiNotifications_Success()
    {
        $mockJob = $this->createMock(Job::class);
        $mockJob->expects($this->never())
                ->method('fail')
                ->withAnyParameters();
        $job = new SendEmptyWikiNotificationsJob();
        $job->setJob($mockJob);
        $job->handle();
    }

    // empty wikis, that are older than 30 days, trigger a notification
    public function testEmptyWikiNotifications_SendNotification()
    {
        $thresholdDaysAgo = Carbon::now()->subDays(
            config('wbstack.wiki_empty_notification_threshold')
        )->toDateTimeString();

        Notification::fake();
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create(['created_at' => $thresholdDaysAgo]);
        $manager = WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);
        $wiki->wikiLifecycleEvents()->updateOrCreate(['first_edited' => null]);

        $job = new SendEmptyWikiNotificationsJob();
        $job->handle();

        Notification::assertSentTo(
            $user->select('email')->get(),
            EmptyWikiNotification::class
        );
    }


    // fresh wiki that does not have lifecycle event records yet
    public function testEmptyWikiNotifications_FreshWiki()
    {
        $now = Carbon::now()->toDateTimeString();

        Notification::fake();
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create(['created_at' => $now]);
        $manager = WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        $job = new SendEmptyWikiNotificationsJob();
        $job->handle();

        Notification::assertNothingSent();
    }

    // non-empty wikis which are older than 30 days do not trigger notifications
    public function testEmptyWikiNotifications_ActiveWiki()
    {
        $thresholdDaysAgo = Carbon::now()->subDays(
            config('wbstack.wiki_empty_notification_threshold')
        )->toDateTimeString();

        $now = Carbon::now()->toDateTimeString();

        Notification::fake();
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create(['created_at' => $thresholdDaysAgo]);
        $manager = WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        WikiLifecycleEvents::factory()->create([
            'wiki_id' => $wiki->id,
            'first_edited' => $now
        ]);

        $job = new SendEmptyWikiNotificationsJob();
        $job->handle();

        Notification::assertNothingSent();
    }

    // notifications do not get sent again
    public function testEmptyWikiNotifications_EmptyNotificationReceived()
    {
        $thresholdDaysAgo = Carbon::now()->subDays(
            config('wbstack.wiki_empty_notification_threshold')
        )->toDateTimeString();

        Notification::fake();
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create(['created_at' => $thresholdDaysAgo]);
        $manager = WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        WikiNotificationSentRecord::factory()->create([
            'wiki_id' => $wiki->id,
            'notification_type' => EmptyWikiNotification::TYPE,
            'user_id' => $manager->user_id,
        ]);

        $job = new SendEmptyWikiNotificationsJob();
        $job->handle();

        Notification::assertNothingSent();
    }
}
