<?php

namespace App\Console\Commands\User;

use App\User;
use Illuminate\Console\Command;

class Verify extends Command
{
    protected $signature = 'wbs-user:verify {email} {verificationState}';

    protected $description = 'Set verification state for user';

    public function handle(): int
    {
        $email = $this->argument('email');
        $state = (int) $this->argument('verificationState');

        $user = User::whereEmail($email)->first();
        if (! $user) {
            $this->error("User not found for $email");

            return 1;
        }

        $user->verified = $state;
        if ($user->save()) {
            $this->line("Marked $email as ".($state ? 'verified' : 'not verified'));
        } else {
            $this->error("Failed to update $email");
        }

        return 0;
    }
}
