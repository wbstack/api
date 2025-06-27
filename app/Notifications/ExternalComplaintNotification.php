<?php

namespace App\Notifications;

use App\Notifications\ComplaintNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

/**
 * A notification to be sent when the legal complaint form is being used.
 */
class ExternalComplaintNotification extends ComplaintNotification
{
    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $mailFrom = config('app.complaint-mail-sender');
        $mailSubject = config('app.name') . ': Report of Illegal Content';

        return (new MailMessage)
            ->from($mailFrom)
            ->subject($mailSubject)
            ->line(Lang::get('Your message via the wikibase.cloud form for reporting illegal content has been submitted.'))
            ->line(Lang::get('Name: ') . $this->name)
            ->line(Lang::get('Email address: ') . $this->mailAddress)
            ->line(Lang::get('Reason why the information in question is illegal content:'))
            ->line($this->reason)
            ->line('---')
            ->line(Lang::get('URL(s) for the content in question:'))
            ->line($this->offendingUrls)
            ->line('---');
    }
}
