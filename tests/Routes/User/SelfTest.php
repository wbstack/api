<?php

namespace Tests\Routes\User;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\Routes\Traits\PostRequestNeedAuthentication;
use Tests\TestCase;

class SelfTest extends TestCase
{
    protected $route = 'user/self';

    use OptionsRequestAllowed;
    use PostRequestNeedAuthentication;
    use RefreshDatabase;

    public function testGet()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api')
          ->post($this->route)
          ->assertStatus(200);
    }
}
