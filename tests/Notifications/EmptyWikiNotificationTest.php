<?php
namespace Tests\Notifications\EmptyWiki;

use App\Notifications\EmptyWikiNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Mockery\Matcher\Not;
use Tests\TestCase;

class EmptyWikiNotificationTest extends TestCase
{
//    protected $route = ''

    public function testEmptyWikiNotification_Success()
    {
        Notification::fake();

    }

    public function testEmptyWikiNotification_Failure()
    {

    }
}
