<?php

namespace App\Jobs;

use App\Notifications\UserCreationNotification;
use App\User;
use App\UserVerificationToken;

class UserCreationVerificationCreateTokenAndSendJob extends Job
{

    /**
     * @var User
     */
    private $user;

    /**
     * @return void
     */
    public function __construct( User $user)
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
        $this->user->notify(new UserCreationNotification($emailToken));
    }
}
