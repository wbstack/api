<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\HtmlString;

class PolicyAnnouncementNotification extends Notification {
    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage {
        return (new MailMessage())
            ->from('noreply@wikibase.cloud', 'Wikibase Cloud')
            ->subject(Lang::get('Please review and accept the updated Terms of Use and new Hosting Policy'))
            ->greeting(Lang::get('Dear Wikibase Cloud user,'))
            ->line(new HtmlString(Lang::get('We\'ve made two updates to the agreements that help run and maintain Wikibase Cloud. Please review and accept them if you agree. You can do it by logging in to the <a href="https://www.wikibase.cloud/">website</a>.')))
            ->line('')
            ->line(new HtmlString(Lang::get('What are we talking about and why is that important?')))
            ->line('')
            ->line(new HtmlString(Lang::get('<p><strong>1. Updated Terms of Use</strong></p>')))
            ->line(Lang::get('In order to comply with the European Union\'s Digital Services Act (DSA) and to build more transparency on how Wikibase Cloud works, we\'ve revised our Terms of Use. The main additions are a clear way to report illegal content, an explanation of how we handle content moderation, and a complaints-and-appeals process. These changes don\'t affect how you build or run your Wikibases.'))
            ->line('')
            ->line(new HtmlString(Lang::get('<p><strong>2. New Hosting Policy</strong></p>')))
            ->line(new HtmlString(Lang::get('We\'re introducing a Hosting Policy for the first time. As a mission-driven non-profit, Wikimedia Deutschland wants Wikibase Cloud to be used purposefully and in line with our mission, that is: hosting open knowledge that serves the public and strengthens the wider Wikibase Ecosystem. The policy indicates driving factors for what the platform is for as well as the kinds of projects it\'s here to support, and lastly the basic expectations for Wikibases hosted on it.')))
            ->line('')
            ->line(new HtmlString(Lang::get('For now, we ask you to read through the documents when you log in next, and decide whether you agree with them. We want to add that we can no longer maintain your Wikibase in case you do not agree with these changes. Read the <a href="https://www.wikibase.cloud/terms-of-use">Terms of Use</a> and the <a href="https://www.wikibase.cloud/hosting-policy">Hosting Policy</a>.')))
            ->line('')
            ->line(Lang::get('Please note that nothing changes for your Wikibase today.'))
            ->line('')
            ->line(new HtmlString(Lang::get('<p><strong>One thing to know for later</strong></p>')))
            ->line(Lang::get('Starting at the end of September, the Hosting Policy introduces a temporary-by-default model. To keep your Wikibase online long-term, you\'ll submit it for a short review, and you\'ll have 3 months to do so. We\'ll explain exactly how later - no Wikibase will be affected without clear notice.'))
            ->line('')
            ->line(Lang::get('We\'d encourage you to read the Hosting Policy now and consider whether your Wikibase fits what Wikibase Cloud is for. If it\'s a good fit, the review will be straightforward. If it isn\'t, it\'s better to know early - you\'ll have time to export your data and find a new home for it by the end of the year.'))
            ->line('')
            ->line(new HtmlString(Lang::get('Feel free to <a href="https://www.wikibase.cloud/contact">contact us</a> if any of the above needs clarification on your end.')))
            ->line('')
            ->line(Lang::get('Thanks for embarking on this journey with us!'))
            ->line('')
            ->line(Lang::get('The Wikibase Cloud team'));
    }
}
