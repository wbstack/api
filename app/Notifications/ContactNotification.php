<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

/**
 * A notification to be sent when the contact form is being used.
 */
class ContactNotification extends Notification
{
    public $name;
    public $subject;
    public $message;
    public $contactDetails;

    /**
     * The callback that should be used to build the mail message.
     *
     * @var \Closure|null
     */
    public static $toMailCallback;

    /**
     * Create a notification instance.
     *
     * @param  string  $token
     * @param  string  $name
     * @param  string  $message
     * @param  string  $contactDetails
     * @return void
     */
    public function __construct($name, $subject, $message, $contactDetails='')
    {
        $this->name = $name;
        $this->subject = Lang::get('contact.' . $subject);
        $this->message = $message;
        $this->contactDetails = $contactDetails;
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
        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $this->token);
        }

        //$verifyEmailLink = config('wbstack.ui_url') . '/emailVerification/'.$this->token;

        return (new MailMessage)
            ->subject(config('app.name') . ' ' . Lang::get(' contact form message: ') . $this->subject)
            ->line(Lang::get('A message via the wikibase.cloud contact form has been submitted.'))
            ->line(Lang::get('Name: ').$this->name)
            ->line(Lang::get('Contact details: '). ($this->contactDetails?$this->contactDetails:'None'))
            ->line(Lang::get('Subject: ') . $this->subject)
            ->line(Lang::get('Message:'))
            ->line($this->message);
    }

    /**
     * Set a callback that should be used when building the notification mail message.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public static function toMailUsing($callback)
    {
        static::$toMailCallback = $callback;
    }
}
