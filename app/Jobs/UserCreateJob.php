<?php

namespace App\Jobs;

use App\TermsOfUseVersion;
use App\User;
use App\UserTermsOfUseAcceptance;
use Illuminate\Support\Facades\Hash;

class UserCreateJob extends Job {
    private $email;

    private $password;

    private $verified;

    public function __construct($email, $password, $verified = false) {
        // TODO maybe pass in an unsaved eloquent model?
        // // but that would make CLI job creation hard
        $this->email = $email;
        $this->password = $password;
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

        UserTermsOfUseAcceptance::create([
            'user_id' => $user->id,
            'tou_version' => TermsOfUseVersion::latestVersion()->version,
            'tou_accepted_at' => now(),
        ]);

        return $user;
    }
}
