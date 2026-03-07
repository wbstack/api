<?php

namespace App\Jobs;

use App\TermsOfUseVersion;
use App\User;
use App\UserTermsOfUseAcceptance;
use Illuminate\Bus\Batchable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Bug: T401165 https://phabricator.wikimedia.org/T401165
 * Job to record Terms of Use acceptance for all preexisting users.
 * This job should only be run ONCE to seed the data for terms of use users have agreed to before we started tracking it explicitly.
 * This job iterates through all users and creates a UserTermsOfUseAcceptance record
 *  for each, using the latest (only) Terms of Use version and the user's creation date as ToU acceptance date.
 * Errors during processing are logged and the job is marked as failed if accepting the terms of use for any user fails.
 */
class UserTouAcceptanceJob extends Job {
    use Batchable;
    use Dispatchable;

    public function handle(): void {
        $users = User::all();
        foreach ($users as $user) {
            try {
                UserTermsOfUseAcceptance::create([
                    'user_id' => $user->id,
                    'tou_version' => TermsOfUseVersion::getActiveVersion()->version,
                    'tou_accepted_at' => $user->created_at,
                ]);
            } catch (Throwable $exception) {
                Log::error("Failure processing user {$user->email} for UserTouAcceptanceJob: {$exception->getMessage()}");
                $this->fail($exception);
            }
        }
    }
}
