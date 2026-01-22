<?php

namespace App\Console\Commands\User;

use App\Services\WikiUserEmailChecker;
use App\User;
use Illuminate\Console\Command;

class CheckUserEmailExist extends Command {
    protected $signature = 'wbs-user:check-email {emails*}';

    protected $description = 'Check if emails exist in apidb.users or any MediaWiki user table';

    public function handle(WikiUserEmailChecker $emailChecker): int {
        $emails = $this->argument('emails');
        foreach ($emails as $email) {
            $found = false;

            if (User::whereEmailInsensitive($email)->exists()) {
                $this->line("FOUND: {$email} in apidb.users");
                $found = true;
            }

            $mwResults = $emailChecker->findEmail($email);

            foreach ($mwResults as $location) {
                $this->line("FOUND: {$email} in {$location}");
                $found = true;
            }

            if (!$found) {
                $this->line("NOT FOUND: {$email}");
            }

            $this->line('-------------------------------------------------');
        }

        return 0;
    }
}
