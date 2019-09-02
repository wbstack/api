<?php

namespace App\Tests\Routes\Admin\Invitation;

use App\Tests\TestCase;
use App\Tests\Routes\Traits\OptionsRequestAllowed;
use App\Tests\Routes\Traits\CrossSiteHeadersOnOptions;

class ListTest extends TestCase
{
    protected $route = 'admin/invitation/list';

    use CrossSiteHeadersOnOptions;
    use OptionsRequestAllowed;
}
