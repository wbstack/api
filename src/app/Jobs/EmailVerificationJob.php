<?php

namespace App\Jobs;

use App\Mail\UserVerification;
use App\User;
use Illuminate\Support\Facades\Mail;

class EmailVerificationJob extends Job
{

  private $user;
  private $token;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( User $user, $token )
    {
        $this->user = $user;
        $this->token = $token;
    }

    /**
     * Execute the job.
     *
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
