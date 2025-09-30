<?php

namespace Tests\Feature;

use App\Jobs\UserCreateJob;
use App\TermOfUseVersion;
use App\UserTermOfUseAcceptance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserCreateJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_creation_creates_terms_of_use_acceptance_record(): void
    {
        $email = 'test+'.uniqid().'@example.com';
        $password = 'secret123';

        $user = (new UserCreateJob($email, $password, true))->handle();

        $this->assertDatabaseHas('tou_acceptances', [
            'user_id' => $user->id,
            'tou_version' => TermOfUseVersion::latest()->value,
        ]);

        $acceptance = UserTermOfUseAcceptance::where('user_id', $user->id)->first();
        $this->assertNotNull($acceptance, 'Acceptance row missing');
        $this->assertNotNull($acceptance->tou_accepted_at, 'tou_accepted_at should be set');

        $this->assertEquals(1, DB::table('tou_acceptances')->where('user_id', $user->id)->count());
    }
}
