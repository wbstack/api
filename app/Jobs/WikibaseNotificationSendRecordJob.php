<?php

namespace App\Jobs;

use App\Notifications\EmptyWikibaseNotification;
use App\Wiki;
use App\User;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Log;

class WikibaseNotificationSendRecordJob extends Job implements ShouldBeUnique
{
    public function handle (): void
    {
        $allWikis = Wiki::all();
        foreach ($allWikis as $wiki) {
            try {
                $this->updateWikibaseNotificationSendRecord($wiki);
            } catch (\Exception $exception) {
                $this->job->markAsFailed();
                Log::error(
                    'Failure polling wiki '.$wiki->getAttribute('domain').' for sitestats: '.$exception->getMessage()
                );
            }
        }
    }

    public function updateWikibaseNotificationSendRecord (Wiki $wiki): void
    {
        $update = [];

        //Calculate how many days has passed since the wikibase instance was first created
        $firstEdited = $wiki->first_edited;
        $createdAt = $wiki->created_at->timestamp;
        $now = time();
        $dateDiff = ($now - $createdAt) / (60 * 60 * 24);

        $user = $wiki->wikiManagers()->get('user_id')->first()->first();

        if ($firstEdited == null && $dateDiff >= 30) {
            try {
                $user->notify(new EmptyWikibaseNotification());
                $update['notification_type'] = 'empty_wikibase_notification'; //We can do Enum for other kind of notification when we can update PHP to >=8.1
            } catch (\Exception $exception) {
                $this->job->markAsFailed();
                Log::error(
                    'Notifying Empty Wikibase '.$wiki->getAttribute('domain'). 'owner failed for sitestats: '.$exception->getMessage()
                );
            }
        }
        $wiki->wikibaseNotificationSendRecord()->updateOrCreate($update);
    }
}
