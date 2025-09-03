<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

/**
 * A notification to be sent when the legal complaint form is being used.
 */
class ComplaintNotificationExternal extends ComplaintNotification {
    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable) {
        $name = $this->name;

        if (empty($name)) {
            $name = 'None';
        }

        $mailFrom = config('app.complaint-mail-sender');
        $mailSubject = config('app.name') . ': Report of Illegal Content';

        return (new MailMessage)
            ->from($mailFrom)
            ->subject($mailSubject)
            ->line(Lang::get('Your message via the wikibase.cloud form for reporting illegal content has been submitted.'))
            ->line('---');
    }
}
