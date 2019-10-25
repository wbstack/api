<?php

namespace App\Jobs;

use App\User;
use Illuminate\Support\Facades\Mail;

class SendUserEmailVerificationJob extends Job
{
    private $user;

    private $token;

    /**
     * @return void
     */
    public function __construct(User $user, $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    /**
     * @return void
     */
    public function handle()
    {
        // TODO pretty email....
        $text = 'An account was recently created with your email address.'.PHP_EOL;
        $text = $text.'Please verify your email by following the link below.'.PHP_EOL;
        $text = $text.'If this account was not created by you, please do nothing.'.PHP_EOL;
        // TODO don't hard code..
        $text = $text.'https://www.wbstack.com/emailVerification/'.$this->token.PHP_EOL;
        Mail::raw($text, function ($message) {
            $message->to($this->user->email)
          ->subject('WbStack User Email Verification');
        });
    }
}
