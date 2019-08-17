<?php

namespace App\Tests\Routes\Admin\Invitation;

use App\Tests\TestCase;
use App\Tests\Routes\Traits\CrossSiteHeadersOnOptions;
use App\Tests\Routes\Traits\OptionsRequestAllowed;

class ListTest extends TestCase {

    protected $route = 'admin/invitation/list';

    use CrossSiteHeadersOnOptions;
    use OptionsRequestAllowed;

}
