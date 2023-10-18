<?php

namespace App\Jobs;

use App\Notifications\EmptyWikibaseNotification;
use App\WikibaseNotificationSendRecord;
use App\WikiLifecycleEvents;
use App\Wiki;
use App\User;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Log;
use MediaWiki\User\UserIdentity;

class WikibaseNotificationSendRecordJob extends Job implements ShouldBeUnique
{
    public function handle (): void
    {
        $allWikis = Wiki::all();
        foreach ($allWikis as $wiki) {
            try {
                $this->updateWikibaseNotificationSendRecord($wiki);
            } catch (\Exception $ex) {
                $this->job->markAsFailed();
                Log::error(
                    'Failure polling wiki '.$wiki->getAttribute('domain').' for sitestats: '.$ex->getMessage()
                );
            }
        }
    }

    public function updateWikibaseNotificationSendRecord (Wiki $wiki): void
    {
        $update = [];

        //Calculate how many days has passed since the wikibase instance was first created
        $firstEdited = $wiki->wikiLifecycleEvents()->get(['first_edited']);
        $createdAt = $wiki->created_at->timestamp;
        $now = time();
        $dateDiff = ($now - $createdAt) / (60 * 60 * 24);

        $sitename = $wiki->sitename;

        $userID = $wiki->wikiManagers->get('user_id');
        $user = User::whereId($userID);

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
