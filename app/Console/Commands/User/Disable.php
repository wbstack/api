<?php

namespace App\Console\Commands\User;

/**
 * Disables a user account, deletes information about their email address and their password hash.
 * Requires the user to manage zero wikis.
 */

use App\User;
use App\WikiManager;
use Illuminate\Console\Command;

class Disable extends Command {
    protected $signature = 'wbs-user:disable {--email=}';

    protected $description = 'Disable user account';

    public function handle(): int {
        $email = $this->option('email');

        $user = User::whereEmail($email)->first();

        if (empty($email)) {
            $this->error("Error: no email address provided. usage: wbs-user:disable --email='mail@address.com'");

            return 1;
        }

        if (!$user) {
            $this->error("Error: Could not find a user for '$email'.");

            return 2;
        }

        $userWikiManagers = WikiManager::whereUserId($user->id)->with('wiki')->get();
        $undeletedWikis = [];

        foreach ($userWikiManagers as $userWikiManager) {
            $userWiki = $userWikiManager->wiki;

            if ($userWiki !== null) {
                $undeletedWikis[] = $userWiki->domain;
            }
        }

        if (!empty($undeletedWikis)) {
            $this->error('Error: User still has wikis: ' . print_r($undeletedWikis, true));

            return 3;
        }

        $userId = $user->id;
        $user->email = random_bytes(10);
        $user->password = random_bytes(10);
        $user->verified = false;

        if ($user->save()) {
            $this->info("Successfully disabled user account with email '$email' (id: '$userId')");
            $this->info('Information about email and password hash was deleted.');

            return 0;
        } else {
            $this->error('Error: Failed to save changes to the database.');

            return 4;
        }
    }
}
