<?php

namespace App\Jobs;

use App\UserVerificationToken;

class UesrVerificationTokenCreateAndSendJob extends Job
{
    /**
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * @return void
     */
    public function handle()
    {
        $emailToken = bin2hex(random_bytes(24));
        UserVerificationToken::create([
        'user_id' => $this->user->id,
        'token' => $emailToken,
      ]);
        dispatch(new SendUserEmailVerificationJob($this->user, $emailToken));
    }
}
