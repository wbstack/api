<?php

namespace Tests\Unit;

use App\Jobs\SendSimpleNotificationJob;
use App\Notifications\SimpleNotification;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendSimpleNotificationJobTest extends TestCase
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
        $user = User::create(['email' => 'foobar@example.com', 'password' => 'asdf']);
        $job = new SendSimpleNotificationJob($user);
        $job->handle();
        Notification::assertSentTo($user, SimpleNotification::class);
    }
}
