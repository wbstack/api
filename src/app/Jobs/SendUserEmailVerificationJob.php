<?php

namespace App\Jobs;

use App\Mail\UserVerification;
use App\User;
use Illuminate\Support\Facades\Mail;

class SendUserEmailVerificationJob extends Job
{

  private $user;
  private $token;

    /**
     * @return void
     */
    public function __construct( User $user, $token )
    {
        $this->user = $user;
        $this->token = $token;
    }

    /**
     * @return void
     */
    public function handle()
    {
      // TODO better email....
      // TODO set subject
      $text = "Please verify your email by following the link below." . PHP_EOL;
      $text = $text . "http://localhost:8081/emailVerification/" . $this->token;
      Mail::raw($text, function($message)
      {
          $message->to($this->user->email);
      });
    }
}
