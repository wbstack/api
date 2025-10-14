<?php

namespace Tests\Jobs;

use App\Jobs\CreateFirstTermsOfUseVersionJob;
use App\Jobs\UserCreateJob;
use App\TermsOfUseVersion;
use App\UserTermsOfUseAcceptance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTermsOfUseAcceptanceTest extends TestCase {
    use RefreshDatabase;

    public function testUserCreationCreatesTouAcceptance(): void {
        (new CreateFirstTermsOfUseVersionJob)->handle();
        $email = 'test+' . uniqid('', true) . '@example.com';
        $user = (new UserCreateJob($email, 'thisisapassword123', true))->handle();

        $this->assertDatabaseHas('tou_acceptances', [
            'user_id' => $user->id,
            'tou_version' => TermsOfUseVersion::latestVersion()->version,
        ]);

        $rows = UserTermsOfUseAcceptance::where('user_id', $user->id)->get();
        $this->assertCount(1, $rows);
        $acceptance = $rows->first();

        $this->assertSame(TermsOfUseVersion::latestVersion()->version, $acceptance->tou_version);
        $this->assertNotNull($acceptance->tou_accepted_at);
    }
}
