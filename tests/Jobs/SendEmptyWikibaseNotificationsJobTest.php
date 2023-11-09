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
use Carbon\Carbon;

class SendEmptyWikibaseNotificationsJobTest extends TestCase
{
    use RefreshDatabase;

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

    public function testEmptyWikibaseNotifications_SendNotification()
    {
        Notification::fake();
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create(['created_at' => '2022-12-31 16:00:00']);
        $manager = WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);
        $wiki->wikiLifecycleEvents()->updateOrCreate(['first_edited' => null]);
//        $wiki->wikibaseNotificationSentRecord()->updateOrCreate(['notification_type' => null]);

        $job = new SendEmptyWikibaseNotificationsJob();
        $job->handle();

        Notification::assertSentTo(
            $user->select('email')->get(),
            EmptyWikibaseNotification::class
        );
    }

    public function testEmptyWikibaseNotifications_ActiveWiki()
    {
        Notification::fake();
        $user = User::factory()->create(['verified' => true]);
        $wiki = Wiki::factory()->create(['created_at' => '2022-12-31 16:00:00']);
        $manager = WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        $first_edited = Carbon::now()->subDays(20);
        WikiLifecycleEvents::factory()->create(['wiki_id' => $wiki->id, 'first_edited' => $first_edited->toDateTimeString()]);

        $job = new SendEmptyWikibaseNotificationsJob();
        $job->handle();

        Notification::assertNothingSent();
    }

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
