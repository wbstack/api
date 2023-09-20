<?php

namespace App\Notifications;

use Closure;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

/**
 * Notification to be sent to empty Wikibase owner if the wiki stay empty longer than 30 days
 */

class EmptyWikiNotification extends Notification
{
    /**
     * The callback that should be used to build the mail message.
     *
     * @var Closure|null
     */
    public static ?Closure $toMailCallback;

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable);
        }

        return (new MailMessage)
            ->subject(Lang::get('Need some help with your Wikibase?'))
            ->line(Lang::get('Thanks for creating a Wikibase instance on Wikibase Cloud! That was at least 30 days ago. We couldn’t help but notice that your Wikibase instance remains empty, so we’re checking in to see if we can help.'))
            ->line(Lang::get('If you’re still planning to use Wikibase for your project but just haven’t gotten around to doing so, no worries -- feel free to ignore this email.'))
            ->line(Lang::get('Are you having trouble getting started? We have some resources that might help:'))
            ->line(Lang::get('Getting started'))
            ->line(Lang::get('Data modeling'))
            ->line(Lang::get('Learn by example'))
            ->line(Lang::get('Get your questions answered: check the FAQ, ask us or ask the community, either on Telegram or the mailing list.'))
            ->line(Lang::get('Have you reconsidered using Wikibase for this project? We’d love it if you’d tell us why. (You can delete your empty Wikibase(s) from your dashboard.)'))
            ->line(Lang::get('Thanks for using Wikibase Cloud!'));
    }

    /**
     * Set a callback that should be used when building the notification mail message.
     *
     * @param \Closure $callback
     * @return void
     */
    public static function toMailUsing(Closure $callback)
    {
        static::$toMailCallback = $callback;
    }
}
