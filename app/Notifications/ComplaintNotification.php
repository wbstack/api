<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

/**
 * A notification to be sent when the legal complaint form is being used.
 */
class ComplaintNotification extends Notification {
    public $offendingUrls;

    public $reason;

    public $name;

    public $mailAddress;

    /**
     * Create a notification instance.
     *
     * @param  string  $offendingUrls
     * @param  string  $reason
     * @param  string  $name
     * @param  string  $mailAddress
     * @return void
     */
    public function __construct($offendingUrls, $reason, $name = null, $mailAddress = null) {
        $this->offendingUrls = $offendingUrls;
        $this->reason = $reason;
        $this->name = $name;
        $this->mailAddress = $mailAddress;
    }

    /**
     * Get the notification's channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via($notifiable) {
        return ['database', 'mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable) {
        $name = $this->name;
        $mailAddress = $this->mailAddress;

        if (empty($name)) {
            $name = 'None';
        }

        if (empty($mailAddress)) {
            $mailAddress = 'None';
        }

        $mailFrom = config('app.complaint-mail-sender');
        $mailSubject = config('app.name') . ': Report of Illegal Content';

        return (new MailMessage)
            ->from($mailFrom)
            ->subject($mailSubject)
            ->line(Lang::get('A message via the wikibase.cloud form for reporting illegal content has been submitted.'))
            ->line(Lang::get('Reporter name: ') . $name)
            ->line(Lang::get('Reporter email address: ') . $mailAddress)
            ->line(Lang::get('Reason why the information in question is illegal content:'))
            ->line($this->reason)
            ->line(Lang::get('URL(s) for the content in question:'))
            ->line($this->offendingUrls)
            ->line('---');
    }

    public function toDatabase($notifiable) {
        $mail = $this->toMail($notifiable);

        return new DatabaseMessage($mail->toArray());
    }
}
