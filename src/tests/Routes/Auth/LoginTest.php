<?php

namespace App\Tests\Routes\Auth;

use App\Tests\TestCase;
use App\Tests\Routes\Traits\CrossSiteHeadersOnOptions;
use App\Tests\Routes\Traits\OptionsRequestAllowed;

class LoginTest extends TestCase {

    protected $route = 'auth/login';

    use CrossSiteHeadersOnOptions;
    use OptionsRequestAllowed;

}
