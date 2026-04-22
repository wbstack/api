<?php

namespace App\Jobs;

use App\UserVerificationToken;
use Carbon\Carbon;

class ExpireOldUserVerificationTokensJob extends Job {
    /**
     * @return void
     */
    public function handle() {
        $oldTokens = UserVerificationToken::whereDate('created_at', '<=', Carbon::now()->subDays(1)->toDateTimeString());
        $oldTokens->delete();
    }
}
