<?php

namespace App\Tests\Routes\Interest;

use App\Tests\TestCase;
use App\Tests\Routes\Traits\CrossSiteHeadersOnOptions;
use App\Tests\Routes\Traits\OptionsRequestAllowed;

class RegisterTest extends TestCase {

    protected $route = 'interest/register';

    use CrossSiteHeadersOnOptions;
    use OptionsRequestAllowed;

}
