<?php

namespace Tests\Routes\User;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\Routes\Traits\PostRequestNeedAuthentication;
use Tests\TestCase;

class SelfTest extends TestCase
{
    protected $route = 'user/self';

    use OptionsRequestAllowed;
    use PostRequestNeedAuthentication;
    use DatabaseTransactions;

    public function testGet()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user, 'api')
          ->post($this->route)
          ->assertStatus(200);
    }
}
