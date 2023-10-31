<?php

namespace App\Jobs;

use App\Notifications\EmptyWikibaseNotification;
use App\Wiki;
use App\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Log;

class SendEmptyWikibaseNotificationsJob extends Job implements ShouldBeUnique
{
    public function handle (): void
    {
        $allWikis = Wiki::all();
        foreach ($allWikis as $wiki) {
            try {
                $this->sendEmptyWikibaseNotification($wiki);
            } catch (\Exception $exception) {
                Log::error(
                    'Failure polling wiki '.$wiki->getAttribute('domain').' for sitestats: '.$exception->getMessage()
                );
            }
        }
    }

    public function sendEmptyWikibaseNotification (Wiki $wiki): void
    {
        //Calculate how many days has passed since the wikibase instance was first created
        $createdAt = $wiki->created_at;
        $now = CarbonImmutable::now();
        $emptyWikibaseDays = $createdAt->diffInDays($now);

        $firstEdited = $wiki->first_edited;

        $thing = $wiki->wikibaseNotificationSentRecord()->first('notification_type');
        echo $thing;

        if ($firstEdited == null && $emptyWikibaseDays >= 30) {
            $user = $wiki->wikiManagers()->first();
            $user->notify(new EmptyWikibaseNotification());
            $wiki->wikibaseNotificationSentRecord()->updateOrCreate(['notification_type'=>'empty_wikibase_notification']);
        }
    }
}
