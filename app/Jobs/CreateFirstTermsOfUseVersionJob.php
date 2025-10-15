<?php

namespace App\Jobs;

use App\TermsOfUseVersion;
use Illuminate\Bus\Batchable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateFirstTermsOfUseVersionJob extends Job {
    use Batchable;
    use Dispatchable;

    public function handle(): void {
        try {
            TermsOfUseVersion::create([
                'version' => '2022-01-01',
                'active' => true,
            ]);
        } catch (Throwable $exception) {
            Log::error("Failure creating initial Terms of Use version: {$exception->getMessage()}");
            $this->fail($exception);
        }
    }
}
