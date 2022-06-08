<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

/**
 * Internal summary email with platform stats
 */
class PlatformStatsSummaryNotification extends Notification
{
    private $summary;

    /**
     * Create a notification instance.
     *
     * @return void
     */
    public function __construct($summary)
    {
        $this->summary = $summary;
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
        return (new MailMessage)
            ->subject(Lang::get('Platform summary email'))
            ->line(Lang::get('This email is automatically generated.'))
            ->line(Lang::get("---------------------------------------"))
            ->line(Lang::get("Total wikis: {$this->summary['total']}"))
            ->line(Lang::get("Active wikis: {$this->summary['active']}"))
            ->line(Lang::get("Inactive wikis: {$this->summary['inactive']}"))
            ->line(Lang::get("Deleted wikis: {$this->summary['deleted']}"))
            ->line(Lang::get("Empty wikis: {$this->summary['empty']}"));

    }
}
