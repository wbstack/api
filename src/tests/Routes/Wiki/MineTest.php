<?php

namespace App\Tests\Routes\Wiki\Managers;

use App\Tests\TestCase;
use App\Tests\Routes\Traits\OptionsRequestAllowed;
use App\Tests\Routes\Traits\CrossSiteHeadersOnOptions;

class MineTest extends TestCase
{
    protected $route = 'wiki/mine';

    use CrossSiteHeadersOnOptions;
    use OptionsRequestAllowed;
}
