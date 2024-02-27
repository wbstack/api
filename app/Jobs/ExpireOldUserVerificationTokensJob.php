<?php

namespace App\Jobs;

use App\UserVerificationToken;

class ExpireOldUserVerificationTokensJob extends Job
{
    public function __construct()
    {
        $this->onQueue(Queue::Cleanup);
    }
    /**
     * @return void
     */
    public function handle()
    {
        $oldTokens = UserVerificationToken::whereDate('created_at', '<=', \Carbon\Carbon::now()->subDays(1)->toDateTimeString());
        $oldTokens->delete();
    }
}
