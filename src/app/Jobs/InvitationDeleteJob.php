<?php

namespace App\Jobs;

use App\Invitation;

class InvitationDeleteJob extends Job
{
    /**
     * @return void
     */
    public function __construct( $code )
    {
        $this->code = strtolower($code);
    }

    /**
     * @return void
     */
    public function handle()
    {
      $invite = Invitation::where('code', $this->code)->first();
      if( $invite ) {
        $invite->delete();
      }
      // TODO optionally fail if the code isn't there?
    }
}
