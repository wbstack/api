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
    public function handle (): void
    {
        $wikis = Wiki::with(['wikiLifecycleEvents'])->get();

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
            $wiki->wikiNotificationSentRecords()->create(['notification_type' => EmptyWikiNotification::TYPE]);
            $wikiManager->notify(new EmptyWikiNotification($wiki->sitename));    
        }
    }
}
