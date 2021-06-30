<?php

namespace App\Jobs;

use App\Invitation;

class InvitationCreateJob extends Job
{
    private $code;

    public function __construct(string $code)
    {
        $this->code = strtolower($code);
    }

    /**
     * @return Invitation|null
     */
    public function handle()
    {
        $test = Invitation::where('code', $this->code)->first();
        if ($test) {
            $this->fail(
                new \RuntimeException('Invitation code already existed')
            );

            return; //safegaurd
        }

        return Invitation::create([
          'code' => $this->code,
      ]);
    }
}
