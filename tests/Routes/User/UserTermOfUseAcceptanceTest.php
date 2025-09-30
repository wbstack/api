<?php

namespace Tests\Routes\User;

use App\Jobs\UserCreateJob;
use App\TermOfUseVersion;
use App\UserTermOfUseAcceptance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTermOfUseAcceptanceTest extends TestCase {
    use RefreshDatabase;

    public function testUserCreationCreatesTouAcceptance(): void {
        $email = 'test+' . uniqid('', true) . '@example.com';
        $user = (new UserCreateJob($email, 'thisisapassword123', true))->handle();

        $this->assertDatabaseHas('tou_acceptances', [
            'user_id' => $user->id,
            'tou_version' => TermOfUseVersion::latest()->value,
        ]);

        $rows = UserTermOfUseAcceptance::where('user_id', $user->id)->get();
        $this->assertCount(1, $rows);
        $acceptance = $rows->first();

        $this->assertInstanceOf(TermOfUseVersion::class, $acceptance->tou_version);
        $this->assertSame(TermOfUseVersion::latest(), $acceptance->tou_version);
        $this->assertNotNull($acceptance->tou_accepted_at);
    }
}
