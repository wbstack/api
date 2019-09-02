<?php

namespace Tests\Routes\User;

use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Routes\Traits\OptionsRequestAllowed;

class SelfTest extends TestCase
{
    protected $route = 'user/self';

    use OptionsRequestAllowed;
    use DatabaseTransactions;

    public function testGet()
    {
        // TODO fix InvalidArgumentException: Auth driver [api] for guard [api] is not defined
        $this->markTestSkipped();
        $user = factory(User::class)->create();
        $this->actingAs($user)
          ->get($this->route)
          ->assertStatus(200);
    }
}
