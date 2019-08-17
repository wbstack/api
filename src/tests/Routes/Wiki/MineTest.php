<?php

namespace App\Tests\Routes\Wiki\Managers;

use App\Tests\TestCase;
use App\Tests\Routes\Traits\CrossSiteHeadersOnOptions;
use App\Tests\Routes\Traits\OptionsRequestAllowed;

class MineTest extends TestCase {

    protected $route = 'wiki/mine';

    use CrossSiteHeadersOnOptions;
    use OptionsRequestAllowed;

}
