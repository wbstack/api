<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

/**
 * A notification to be sent when an account has already been created, but the verification link has been lost, or has expired
 * and a new link is needed by the user.
 */
class EmailReverificationNotification extends Notification
{
    /**
     * The email verification token.
     *
     * @var string
     */
    public $token;

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
     * @return void
     */
    public function __construct($token)
    {
        $this->token = $token;
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

        /*
         * This is another point in Laravel where the default expectation of the UI living within the control of Laravel
         * bites us a bit.
         * Ideally we would be able to have the route password.reset etc actually direct to the UI VUE route
         * Then we could use the ResetPassword Notification notification too...
         * TODO this is now duplicated in a few notifications.... TODO switch on ENV instead?
         */
        $verifyEmailLink = config('app.url') . '/emailVerification/'.$this->token;

        return (new MailMessage)
            ->subject(Lang::get('Email Verification'))
            ->line(Lang::get('Someone, probably you, has requested another email verification link.'))
            ->line(Lang::get('You can get started in seconds — just click below to begin.'))
            ->action(Lang::get('Verify Email'), $verifyEmailLink);
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
