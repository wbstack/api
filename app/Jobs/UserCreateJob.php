<?php

namespace App\Jobs;

use App\TermsOfUseVersion;
use App\User;
use App\UserTermsOfUseAcceptance;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserCreateJob extends Job {
    private $verified;

    public function __construct(private $email, private $password, $verified = false) {
        $this->verified = false;
    }

    /**
     * @return User
     */
    public function handle() {
        $user = User::create([
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'verified' => $this->verified,
        ]);

        $latest = TermsOfUseVersion::latestActiveVersion();
        if ($latest) {
            UserTermsOfUseAcceptance::create([
                'user_id' => $user->id,
                'tou_version' => $latest->version,
                'tou_accepted_at' => now(),
            ]);
        } else {
            Log::warning("No active Terms of Use version found when creating user {$user->email} (ID {$user->id}).");
        }

        return $user;
    }
}
