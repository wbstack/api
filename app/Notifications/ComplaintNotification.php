<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

/**
 * A notification to be sent when the legal complaint form is being used.
 */
class ComplaintNotification extends Notification
{
    public $textUrls;
    public $textReason;
    public $name;
    public $mailAddress;

    /**
     * Create a notification instance.
     *
     * @param  string  $textUrls
     * @param  string  $textReason
     * @param  string  $name
     * @param  string  $mailAddress
     * @return void
     */
    public function __construct($textUrls, $textReason, $name='', $mailAddress='')
    {
        $this->textUrls = $textUrls;
        $this->textReason = $textReason;
        $this->name = $name;
        $this->mailAddress = $mailAddress;
    }

    /**
     * Get the notification's channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $mailAddress = $this->mailAddress ? $this->mailAddress:'None';
        $name = $this->name ? $this->name:'None';

        $mailFrom = config('app.complaint-mail-sender');
        $mailSubject = config('app.name') . ': Report of Illegal Content';

        return (new MailMessage)
            ->from($mailFrom)
            ->subject($mailSubject)
            ->line(Lang::get('A message via the wikibase.cloud form for reporting illegal content has been submitted.'))
            ->line(Lang::get('Reporter name: ') . $name)
            ->line(Lang::get('Reporter email address: ') . $mailAddress)
            ->line(Lang::get('Reason:'))
            ->line($this->textReason)
            ->line('---')
            ->line(Lang::get('Reported URLs:'))
            ->line($this->textUrls)
            ->line('---');
    }
}
