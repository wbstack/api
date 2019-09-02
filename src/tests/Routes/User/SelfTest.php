<?php

namespace App\Tests\Routes\User;

use App\User;
use App\Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Tests\Routes\Traits\OptionsRequestAllowed;
use App\Tests\Routes\Traits\CrossSiteHeadersOnOptions;

class SelfTest extends TestCase
{
    protected $route = 'user/self';

    use CrossSiteHeadersOnOptions;
    use OptionsRequestAllowed;
    use DatabaseTransactions;

    public function testGet()
    {
        // TODO fix InvalidArgumentException: Auth driver [api] for guard [api] is not defined
        $this->markTestSkipped();
        $user = factory(User::class)->create();
        $this->actingAs($user)
          ->get($this->route)
          ->seeStatusCode(200);
    }
}
