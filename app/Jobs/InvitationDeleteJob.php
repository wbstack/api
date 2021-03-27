<?php

namespace App\Jobs;

use App\Invitation;

class InvitationDeleteJob extends Job
{

    private $code;

    /**
     * @return void
     */
    public function __construct(string $code)
    {
        $this->code = strtolower($code);
    }

    public function handle()
    {
        $invite = Invitation::where('code', $this->code)->first();
        if ($invite) {
            $invite->delete();
        } else {
            $this->fail(
                new \RuntimeException( 'Invitation not found, so can\'t delete' )
            );
            return;//safegaurd
        }
    }
}
