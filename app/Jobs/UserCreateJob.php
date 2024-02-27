<?php

namespace App\Jobs;

use App\User;
use Illuminate\Support\Facades\Hash;

class UserCreateJob extends Job
{
    private $email;

    private $password;

    private $verified;

    public function __construct($email, $password, $verified = false)
    {
        // TODO maybe pass in an unsaved eloquent model?
        // // but that would make CLI job creation hard
        $this->email = $email;
        $this->password = $password;
        $this->verified = false;
        $this->onQueue(Queue::Provisioning);
    }

    /**
     * @return User
     */
    public function handle()
    {
        $user = User::create([
          'email' => $this->email,
          'password' => Hash::make($this->password),
          'verified' => $this->verified,
      ]);

        return $user;
    }
}
