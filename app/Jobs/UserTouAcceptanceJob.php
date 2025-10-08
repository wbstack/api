<?php

namespace App\Jobs;

use App\TermsOfUseVersion;
use App\User;
use App\UserTermsOfUseAcceptance;
use Illuminate\Bus\Batchable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserTouAcceptanceJob extends Job {
    use Batchable;
    use Dispatchable;

    private $users;

    public function __construct($users) {
        $this->users = $users;
    }

    public function handle(): void {
        $this->users = User::all();
        foreach ($this->users as $user) {
            try {
                UserTermsOfUseAcceptance::create([
                    'user_id' => $user->id,
                    'tou_version' => TermsOfUseVersion::latest(),
                    'tou_accepted_at' => $user->created_at,
                ]);
            } catch (Throwable $exception) {
                Log::error('Failure processing user ' . $user->getAttribute('email') . ' for UserTouAcceptanceJob: ' . $exception->getMessage());
                $this->fail($exception);
            }
        }
    }
}
