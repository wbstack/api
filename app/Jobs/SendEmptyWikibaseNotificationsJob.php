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
                $this->sendEmptyWikibaseNotification($wiki);

        }
    }

    public function sendEmptyWikibaseNotification (Wiki $wiki): void
    {
        //Calculate how many days has passed since the wikibase instance was first created
        $firstEdited = $wiki->first_edited;
        $createdAt = $wiki->created_at;
        $now = CarbonImmutable::now();
        $emptyWikibaseDays = $createdAt->diffInDays($now);

        echo('it is running');
        if ($firstEdited == null && $emptyWikibaseDays >= 30) {
            echo('it is running in the if statement');
            $user = $wiki->wikiManagers()->first();
            echo($user);
                $user->notify(new EmptyWikibaseNotification());
                //We can do Enum for other kind of notification when we can update PHP to >=8.1
//                $wiki->notificationSent()->updateOrCreate(['notification_type'=>'empty_wikibase_notification']);
                echo('it was sent');
        }
    }
}
