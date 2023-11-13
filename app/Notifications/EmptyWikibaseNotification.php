<?php

namespace App\Notifications;

use App\Wiki;
use Closure;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\HtmlString;

/**
 * Notification to be sent to empty Wikibase owner if the wiki stay empty longer than 30 days
 */

class EmptyWikibaseNotification extends Notification
{
    private string $sitename;

    /**
     * Create a notification instance.
     *
     * @param string $sitename
     * @return void
     */
    public function __construct(string $sitename)
    {
        $this->sitename = $sitename;
    }

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
        return (new MailMessage)
            ->from('noreply@wikibase.cloud', 'Wikibase Cloud')
            ->subject(Lang::get('Need some help with your Wikibase?'))
            ->line(Lang::get('Thanks for creating a Wikibase instance on Wikibase Cloud! That was at least 30 days ago. We couldn’t help but notice that your Wikibase instance `'.$this->sitename.'` remains empty, so we’re checking in to see if we can help.'))
            ->line(Lang::get('If you’re still planning to use Wikibase for your project but just haven’t gotten around to doing so, no worries -- feel free to ignore this email.'))
            ->line(Lang::get('Are you having trouble getting started? We have some resources that might help:'))
            ->line(new HtmlString('<ul>'))
            ->line(new HtmlString(Lang::get('<li><a href="https://www.mediawiki.org/wiki/Wikibase/Wikibase.cloud/Initial_setup">Getting started</a></li>')))
            ->line(new HtmlString(Lang::get('<li><a href="https://www.mediawiki.org/wiki/Wikibase/Introduction_to_modeling_data">Data modeling</a></li>')))
            ->line(new HtmlString(Lang::get('<li><a href="https://www.wikibase.cloud/discovery">Learn by example</a></li>')))
            ->line(new HtmlString(Lang::get('<li>Get your questions answered: check the <a href="https://www.mediawiki.org/wiki/Wikibase/FAQ">FAQ</a>, <a href="https://www.wikibase.cloud/contact">ask us</a> or ask the community, either on <a href="https://t.me/joinchat/FgqAnxNQYOeAKmyZTIId9g">Telegram</a> or the <a href="https://lists.wikimedia.org/postorius/lists/wikibase-cloud.lists.wikimedia.org/">mailing list</a></li>')))
            ->line(new HtmlString('</ul>'))
            ->line(new HtmlString(Lang::get('Have you reconsidered using Wikibase for this project? We’d love it if you’d <a href="https://www.wikibase.cloud/contact">tell us why</a>. (You can delete your empty Wikibase(s) from your <a href="https://www.wikibase.cloud/dashboard">dashboard</a>.)')))
            ->line(Lang::get('Thanks for using Wikibase Cloud!'));
    }
}
