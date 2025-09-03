<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

/**
 * A notification to be sent when an account has already been created, but the verification link has been lost, or has expired
 * and a new link is needed by the user.
 */
class EmailReverificationNotification extends Notification {
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
    public function __construct($token) {
        $this->token = $token;
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
        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $this->token);
        }

        $verifyEmailLink = config('wbstack.ui_url') . '/emailVerification/' . $this->token;

        return (new MailMessage)
            ->subject(Lang::get('Please verify your email'))
            ->line(Lang::get('Someone, probably you, has requested another link to verify the email associated with your Wikibase.cloud account. You’re just one step away from taking advantage of all that the platform has to offer. To complete your registration, verify your email address by clicking below.'))
            ->action(Lang::get('Verify Email'), $verifyEmailLink)
            ->line(Lang::get('If you did not make this request, you can simply disregard this email.'));
    }

    /**
     * Set a callback that should be used when building the notification mail message.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public static function toMailUsing($callback) {
        static::$toMailCallback = $callback;
    }
}
