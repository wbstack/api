<?php

namespace App\Jobs;

use App\Notifications\EmptyWikibaseNotification;
use App\Wiki;
use App\WikibaseNotificationSentRecord;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Log;

class SendEmptyWikibaseNotificationsJob extends Job implements ShouldBeUnique
{
    public function handle (): void
    {
        $wikis = Wiki::with(['notification', 'wikiLifecycleEvents'])->get();

        foreach ($wikis as $wiki) {
            try {
                if ($this->checkIfWikiIsOldAndEmpty($wiki)) {
                    $this->sendEmptyWikibaseNotification($wiki);
                }
            } catch (\Exception $exception) {
                Log::error(
                    'Failure processing wiki '.$wiki->getAttribute('domain').' for EmptyWikibaseNotification check: '.$exception->getMessage()
                );
                $this->fail();
            }
        }
    }

    public function checkIfWikiIsOldAndEmpty(Wiki $wiki)
    {
        //Calculate how many days has passed since the wikibase instance was first created
        $createdAt = $wiki->created_at;
        $now = CarbonImmutable::now();
        $emptyWikibaseDays = $createdAt->diffInDays($now);

        $firstEdited = $wiki->wikiLifecycleEvents->first_edited;

        $emptyWikiNotificationCount = WikibaseNotificationSentRecord::where([
            'wiki_id' => $wiki->id,
            'notification_type' => EmptyWikibaseNotification::class]
        )->count();

        if ($firstEdited == null && $emptyWikibaseDays >= 30 && $emptyWikiNotificationCount == 0) {
            return true;
        } else {
            return false;
        }
    }

    public function sendEmptyWikibaseNotification (Wiki $wiki): void
    {
        $user = $wiki->wikiManagers()->first();
        $wiki->wikibaseNotificationSentRecord()->create(['notification_type' => EmptyWikibaseNotification::class]);
        $user->notify(new EmptyWikibaseNotification($wiki->sitename));
    }
}
