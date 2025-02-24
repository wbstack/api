<?php

namespace App\Console\Commands\User;

use App\User;
use Illuminate\Console\Command;
use App\Jobs\UserVerificationCreateTokenAndSendJob;

class ChangeEmail extends Command
{
    protected $signature = 'wbs-user:change-email {--from=} {--to=}';

    protected $description = 'Change e-mail address for user';

    public function handle(): int
    {
        $emailOld = $this->option('from');
        $emailNew = $this->option('to');

        $user = User::whereEmail($emailOld)->first();

        if (!$user) {
            $this->error("Error: Could not find a user for '$emailOld'.");
            return 1;
        }

        if ($emailNew == $emailOld) {
            $this->error("Error: New email matches current email.");
            return 2;
        }

        $user->email = $emailNew;
        $user->verified = false;

        if ($user->save()) {
            UserVerificationCreateTokenAndSendJob::newForReverification($user)->handle();

            $this->info("Successfully changed user email from '$emailOld' to '$emailNew'");
            $this->line("Note: a verification mail was sent to the new address ('$emailNew').");

            return 0;
        } else {
            $this->error("Error: Failed to save changes to the database.");
            return 3;
        }
    }
}
