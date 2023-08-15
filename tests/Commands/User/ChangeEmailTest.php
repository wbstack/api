<?php

namespace Tests\Commands;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Notifications\EmailReverificationNotification;
use Illuminate\Support\Facades\Notification;

class ChangeEmailTest extends TestCase
{
    use DatabaseTransactions;

    public function test()
    {
        Notification::fake();

        $emailOld = 'old@example.com';
        $emailNew = 'new@example.com';

        $oldUser = new User(['email' => $emailOld, 'password' => 'worldsstrongestpassword']);
        $oldUser->save();

        $this->artisan('wbs-user:change-email')
            ->expectsQuestion('Current user address', $emailNew)
            ->expectsOutput("Did not find a user for '$emailNew'. Please try again.")
            ->expectsQuestion('Current user address', $emailOld)
            ->expectsOutput("Found a user for '$emailOld'")
            ->expectsQuestion('New user address', $emailOld)
            ->expectsOutput("New email matches current email. Please provide a different address.")
            ->expectsQuestion('New user address', $emailNew)
            ->expectsConfirmation("Confirm: changing user mail address '$emailOld' to '$emailNew'", "yes")
            ->expectsOutput("Successfully changed user email '$emailOld' to '$emailNew'")
            ->expectsOutput("Note: a verification mail was sent to the new address ('$emailNew').")
            ->assertExitCode(0);

        $newUser = User::firstWhere('email', $emailNew);

        $this->assertSame($oldUser->id, $newUser->id);
        $this->assertSame($newUser->email, $emailNew);
        $this->assertSame($newUser->verified, 0);

        Notification::assertSentTo([$newUser], EmailReverificationNotification::class);
    }
}
