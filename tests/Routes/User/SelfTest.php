<?php

namespace Tests\Routes\User;

use App\User;
use Tests\TestCase;
use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\Routes\Traits\PostRequestNeedAuthentication;
use Illuminate\Foundation\Testing\DatabaseTransactions;

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
