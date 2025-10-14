<?php

namespace App\Jobs;

use App\TermsOfUseVersion;
use App\User;
use App\UserTermsOfUseAcceptance;
use Illuminate\Bus\Batchable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateFirstTermsOfUseVersionJob extends Job{

    use Batchable;
    use Dispatchable;

    public function handle(): void {
        try {
            TermsOfUseVersion::create([
                'version' => 'v0',
                'active' => true,
                'acceptance_deadline' => null,
                'content' => null,
            ]);
        } catch (Throwable $exception) {
            Log::error("Failure creating initial Terms of Use version: {$exception->getMessage()}");
            $this->fail($exception);
        }
    }
}
