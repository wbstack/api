<?php

namespace App\Tests\Routes\Admin\Invitation;

use App\Tests\TestCase;
use App\Tests\Routes\Traits\OptionsRequestAllowed;

class CreateTest extends TestCase {

    protected $route = 'admin/invitation/create';

    use OptionsRequestAllowed;

}
