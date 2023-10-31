<?php

namespace Tests\Unit;

use App\Notifications\SimpleNotification;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SimpleNotificationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function test_example()
    {
        Notification::fake();
        $notification = new SimpleNotification();
        $user = User::create(['email' => 'foobar@example.com', 'password' => 'asdf']);
        $user->notify($notification);
        Notification::assertSentTo($user, SimpleNotification::class);
    }
}
