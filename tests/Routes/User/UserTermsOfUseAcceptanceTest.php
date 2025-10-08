<?php

namespace Tests\Routes\User;

use App\Jobs\UserCreateJob;
use App\TermsOfUseVersion;
use App\UserTermsOfUseAcceptance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTermsOfUseAcceptanceTest extends TestCase {
    use RefreshDatabase;

    public function testUserCreationCreatesTouAcceptance(): void {
        $email = 'test+' . uniqid('', true) . '@example.com';
        $user = (new UserCreateJob($email, 'thisisapassword123', true))->handle();

        $this->assertDatabaseHas('tou_acceptances', [
            'user_id' => $user->id,
            'tou_version' => TermsOfUseVersion::latest()->value,
        ]);

        $rows = UserTermsOfUseAcceptance::where('user_id', $user->id)->get();
        $this->assertCount(1, $rows);
        $acceptance = $rows->first();

        $this->assertInstanceOf(TermsOfUseVersion::class, $acceptance->tou_version);
        $this->assertSame(TermsOfUseVersion::latest(), $acceptance->tou_version);
        $this->assertNotNull($acceptance->tou_accepted_at);
    }
}
