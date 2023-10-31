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
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use function Symfony\Component\Translation\t;

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
        $user = User::factory()->create(['password'=>'fakepassword', 'email' => 'fooandbar@example.com', 'verified'=>true]);
        $wiki = Wiki::factory()->create(['created_at' => '2022-12-31 16:00:00', 'domain'=>'fake.wiki.com', 'sitename'=>'fakewiki']);
        $update = [
            'pages'=>1,
            'articles'=>1,
            'edits'=>1,
            'images'=>1,
            'users'=>1,
            'activeusers'=>1,
            'admins'=>1,
            'jobs'=>1,
            'cirrussearch-article-words'=>1
        ];
        $wiki->wikiSiteStats()->updateOrCreate($update);
        $update1 = ['first_edited'=>null, 'last_edited'=>null];
        $wiki->wikiLifecycleEvents()->updateOrCreate($update1);
        $manager = WikiManager::factory()->create(['wiki_id' => $wiki->id, 'user_id' => $user->id]);

        $job = new SendEmptyWikibaseNotificationsJob();
        $job->handle();

        sleep(4);
        Notification::assertSentTo(
            $user,
            EmptyWikibaseNotification::class
        );
    }
}
