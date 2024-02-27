<?php

namespace App\Jobs;

use App\Notifications\EmailReverificationNotification;
use App\Notifications\UserCreationNotification;
use App\User;
use App\UserVerificationToken;

class UserVerificationCreateTokenAndSendJob extends Job
{
    /**
     * @var User
     */
    private $user;

    /**
     * @var string
     */
    private $notificationClass;

    public static function newForAccountCreation(User $user): self
    {
        return new self($user, UserCreationNotification::class);
    }

    public static function newForReverification(User $user): self
    {
        return new self($user, EmailReverificationNotification::class);
    }

    /**
     * @return void
     */
    public function __construct(User $user, string $notificationClass)
    {
        $this->user = $user;
        $this->notificationClass = $notificationClass;
        if (! class_exists($notificationClass)) {
            throw new \InvalidArgumentException("$notificationClass not found for notification");
        }
        $this->onQueue(Queue::Provisioning);
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
        $this->user->notify(new $this->notificationClass($emailToken));
    }
}
