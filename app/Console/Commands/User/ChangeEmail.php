<?php

namespace App\Console\Commands\User;

use App\User;
use Illuminate\Console\Command;
use App\Jobs\UserVerificationCreateTokenAndSendJob;

class ChangeEmail extends Command
{
    protected $signature = 'wbs-user:change-email';

    protected $description = 'Change e-mail address for user';

    public function handle(): int
    {
        do {
            $emailOld = $this->ask('Current user address');
            $user = User::whereEmail($emailOld)->first();

            if ($user) {
                $this->line("Found a user for '$emailOld'");
            } else {
                $this->warn("Did not find a user for '$emailOld'. Please try again.");
            }
        } while(!$user);

        do {
            $emailNew = $this->ask('New user address');
            
            if ($emailNew == $emailOld) {
                $this->warn("New email matches current email. Please provide a different address.");
            }
        } while ($emailNew == $emailOld);

        if (! $this->confirm("Confirm: changing user mail address '$emailOld' to '$emailNew'")) {
            $this->warn("Aborted changing user email address");
            return 1;
        }

        $user->email = $emailNew;
        $user->verified = false;

        if ($user->save()) {
            UserVerificationCreateTokenAndSendJob::newForReverification($user)->handle();

            $this->info("Successfully changed user email '$emailOld' to '$emailNew'");
            $this->line("Note: a verification mail was sent to the new address ('$emailNew').");

            return 0;
        } else {
            $this->error("Failed to change user email '$emailOld' to '$emailNew'");
            return 2;
        }
    }
}
