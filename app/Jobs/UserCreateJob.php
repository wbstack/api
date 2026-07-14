<?php

namespace App\Jobs;

use App\User;
use App\UserTermsOfUseAcceptance;
use App\Policy;
use App\PolicyAcceptance;
use App\TermsOfUseVersion;
use Illuminate\Support\Facades\Hash;

class UserCreateJob extends Job {
    private $email;

    private $password;

    private $verified;
    
    private $acceptedPolicyIds;
    

    public function __construct($email, $password, $acceptedPolicyIds, $verified = false) {
        // TODO maybe pass in an unsaved eloquent model?
        // // but that would make CLI job creation hard
        $this->email = $email;
        $this->password = $password;
        $this->acceptedPolicyIds = $acceptedPolicyIds;
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

        // accept latest Terms of Use Policy automatically
        $latestToU = TermsOfUseVersion::latestActiveVersion();
        if ($latestToU) {
            UserTermsOfUseAcceptance::create([
                'user_id' => $user->id,
                'tou_version' => $latestToU->version,
                'tou_accepted_at' => now(),
            ]);
        } else {
            Log::warning("No active Terms of Use version found when creating user {$user->email} (ID {$user->id}).");
        }

        if (is_array($this->acceptedPolicyIds)) {
            foreach($this->acceptedPolicyIds as $acceptedPolicyId) {
                $policy = Policy::find($acceptedPolicyId);

                if ($policy) {
                    PolicyAcceptance::create([
                        'user_id' => $user->id,
                        'policy_id' => $policy->id,
                        'accepted_at' => now(),
                    ]);
                } else {
                    Log::warning("Policy ID '{$acceptedPolicyId}' not found when creating user {$user->email} (ID {$user->id}).");
                }
            }
        }

        return $user;

    }
}
