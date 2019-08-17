<?php

namespace App\Tests\Routes\User;

use App\Tests\TestCase;
use App\Tests\Routes\Traits\CrossSiteHeadersOnOptions;
use App\Tests\Routes\Traits\OptionsRequestAllowed;

class SelfTest extends TestCase {

    protected $route = 'user/self';

    use CrossSiteHeadersOnOptions;
    use OptionsRequestAllowed;

}
