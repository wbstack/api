<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

/**
 * A notification to be sent when the contact form is being used.
 */
class ContactNotification extends Notification {
    public $name;

    public $subject;

    public $message;

    public $contactDetails;

    /**
     * Create a notification instance.
     *
     * @param  string  $token
     * @param  string  $name
     * @param  string  $message
     * @param  string  $contactDetails
     * @return void
     */
    public function __construct($name, $subject, $message, $contactDetails = '') {
        $this->name = $name;
        $this->message = $message;
        $this->subject = $subject;
        $this->contactDetails = $contactDetails;
    }

    /**
     * Get the notification's channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via($notifiable) {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable) {
        $subject = Lang::get('contact.' . $this->subject);
        $contactDetails = $this->contactDetails ? $this->contactDetails : 'None';

        $mailFrom = str_replace('<subject>', $this->subject, config('app.contact-mail-sender'));
        $mailSubject = config('app.name') . Lang::get(' contact form message: ') . $subject;

        return (new MailMessage)
            ->from($mailFrom)
            ->subject($mailSubject)
            ->line(Lang::get('A message via the wikibase.cloud contact form has been submitted.'))
            ->line(Lang::get('From: ') . $this->name)
            ->line(Lang::get('Contact details: ') . $contactDetails)
            ->line(Lang::get('Subject: ') . $subject)
            ->line('---')
            ->line(Lang::get('Message:'))
            ->line($this->message)
            ->line('---');
    }
}
