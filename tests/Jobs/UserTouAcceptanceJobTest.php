<?php

namespace Tests\Jobs;

use App\Jobs\CreateFirstTermsOfUseVersionJob;
use App\Jobs\UserTouAcceptanceJob;
use App\TermsOfUseVersion;
use App\User;
use App\UserTermsOfUseAcceptance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserTouAcceptanceJobTest extends TestCase {
    use RefreshDatabase;

    public function testTouAcceptanceJob(): void {
        $t1 = Carbon::parse('2025-01-01 10:00:00');
        $t2 = Carbon::parse('2025-01-02 11:00:00');
        $t3 = Carbon::parse('2025-01-03 12:00:00');

        $u1 = User::factory()->create(['created_at' => $t1]);
        $u2 = User::factory()->create(['created_at' => $t2]);
        $u3 = User::factory()->create(['created_at' => $t3]);

        (new CreateFirstTermsOfUseVersionJob)->handle();
        (new UserTouAcceptanceJob)->handle();

        $latest = TermsOfUseVersion::getActiveVersion()->version;

        $this->assertDatabaseHas('tou_acceptances', ['user_id' => $u1->id, 'tou_version' => $latest]);
        $this->assertDatabaseHas('tou_acceptances', ['user_id' => $u2->id, 'tou_version' => $latest]);
        $this->assertDatabaseHas('tou_acceptances', ['user_id' => $u3->id, 'tou_version' => $latest]);

        $this->assertTrue($t1->equalTo(UserTermsOfUseAcceptance::where('user_id', $u1->id)->firstOrFail()->tou_accepted_at));
        $this->assertTrue($t2->equalTo(UserTermsOfUseAcceptance::where('user_id', $u2->id)->firstOrFail()->tou_accepted_at));
        $this->assertTrue($t3->equalTo(UserTermsOfUseAcceptance::where('user_id', $u3->id)->firstOrFail()->tou_accepted_at));
    }
}
