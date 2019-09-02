<?php

namespace App\Jobs;

use App\Invitation;

class InvitationCreateJob extends Job
{
    private $code;

    /**
     * @return void
     */
    public function __construct($code)
    {
        $this->code = strtolower($code);
    }

    /**
     * @return void
     */
    public function handle()
    {
        $test = Invitation::where('code', $this->code)->first();
        if ($test) {
            // Silent return if it already exits
            // TODO should this fail instead?
            return;
        }

        $invite = Invitation::create([
          'code' => $this->code,
      ]);
    }
}
