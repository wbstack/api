<?php

namespace App\Jobs;

use App\Notifications\EmptyWikiNotification;
use App\Wiki;
use App\WikiNotificationSentRecord;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Log;

class SendEmptyWikiNotificationsJob extends Job implements ShouldBeUnique
{
    public function __construct()
    {
        $this->onQueue(self::QUEUE_NAME_RECURRING);
    }
    public function handle (): void
    {
        $wikis = Wiki::with(['wikiLifecycleEvents'])
            ->has('wikiLifecycleEvents')
            ->get();

        foreach ($wikis as $wiki) {
            try {
                if ($this->checkIfWikiIsOldAndEmpty($wiki)) {
                    $this->sendEmptyWikiNotification($wiki);
                }
            } catch (\Exception $exception) {
                Log::error(
                    'Failure processing wiki '.$wiki->getAttribute('domain').' for EmptyWikiNotification check: '.$exception->getMessage()
                );
                $this->fail();
            }
        }
    }

    public function checkIfWikiIsOldAndEmpty(Wiki $wiki)
    {
        // Calculate how many days has passed since the wiki instance was first created
        $emptyDaysThreshold = config('wbstack.wiki_empty_notification_threshold');
        $createdAt = $wiki->created_at;
        $now = CarbonImmutable::now();
        $emptyWikiDays = $createdAt->diffInDays($now);

        $firstEdited = $wiki->wikiLifecycleEvents->first_edited;

        $emptyWikiNotificationCount = WikiNotificationSentRecord::where([
            'wiki_id' => $wiki->id,
            'notification_type' => EmptyWikiNotification::TYPE
        ])->count();

        if (
            $firstEdited == null &&
            $emptyWikiDays >= $emptyDaysThreshold &&
            $emptyWikiNotificationCount == 0
        ) {
            return true;
        } else {
            return false;
        }
    }

    public function sendEmptyWikiNotification (Wiki $wiki): void
    {
        $wikiManagers = $wiki->wikiManagers()->get();

        foreach($wikiManagers as $wikiManager) {
            // we think the order here matters, so that people do not get spammed in case creating a record fails
            // discussed here https://github.com/wbstack/api/pull/656#discussion_r1392443739
            $wiki->wikiNotificationSentRecords()->create([
                'notification_type' => EmptyWikiNotification::TYPE,
                'user_id' => $wikiManager->pivot->user_id,
            ]);
            $wikiManager->notify(new EmptyWikiNotification($wiki->sitename));
        }
    }
}
