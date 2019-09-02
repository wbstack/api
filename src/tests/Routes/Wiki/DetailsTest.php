<?php

namespace App\Tests\Routes\Wiki\Managers;

use App\Tests\TestCase;
use App\Tests\Routes\Traits\OptionsRequestAllowed;
use App\Tests\Routes\Traits\CrossSiteHeadersOnOptions;

class DetailsTest extends TestCase
{
    protected $route = 'wiki/details';

    use CrossSiteHeadersOnOptions;
    use OptionsRequestAllowed;
}
