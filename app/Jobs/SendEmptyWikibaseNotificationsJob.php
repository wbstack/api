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
        $wikis = Wiki::with('notification')->get();

        foreach ($wikis as $wiki) {
            try {
                $this->sendEmptyWikibaseNotification($wiki);
            } catch (\Exception $exception) {
                Log::error(
                    'Failure polling wiki '.$wiki->getAttribute('domain').' for sitestats: '.$exception->getMessage()
                );
            }
        }
    }

    public function checkIfWikiIsOldAndEmpty(Wiki $wiki)
    {
        //Calculate how many days has passed since the wikibase instance was first created
        $createdAt = $wiki->created_at;
        $now = CarbonImmutable::now();
        $emptyWikibaseDays = $createdAt->diffInDays($now);

        $firstEdited = $wiki->first_edited;

        $sentNotification = WikibaseNotificationSentRecord::where('wiki_id', $wiki->id)->get(['notification_type'])->first(); //we want not just checking the 1st one but any 'empty_wikibase_notification'
        echo $sentNotification;

        if ($firstEdited == null && $emptyWikibaseDays >= 30 && $sentNotification['notification_type'] != 'empty_wikibase_notification') {
            return true;
        }
    }

    public function sendEmptyWikibaseNotification (Wiki $wiki): void
    {
        if ($this->checkIfWikiIsOldAndEmpty($wiki)) {
            $user = $wiki->wikiManagers()->first();
            $user->notify(new EmptyWikibaseNotification($wiki->sitename));
            $wiki->wikibaseNotificationSentRecord()->updateOrCreate(['notification_type' => 'empty_wikibase_notification']);
        }
    }
}
