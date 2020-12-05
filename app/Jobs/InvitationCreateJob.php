<?php

namespace App\Jobs;

use App\Invitation;

class InvitationCreateJob extends Job
{
    private $code;

    public function __construct( string $code)
    {
        $this->code = strtolower($code);
    }

    public function handle()
    {
        $test = Invitation::where('code', $this->code)->first();
        if ($test) {
            return false;
        }

        return Invitation::create([
          'code' => $this->code,
      ]);
    }
}
