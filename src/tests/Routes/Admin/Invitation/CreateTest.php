<?php

namespace Tests\Routes\Admin\Invitation;

use Tests\TestCase;
use Tests\Routes\Traits\OptionsRequestAllowed;

class CreateTest extends TestCase
{
    protected $route = 'admin/invitation/create';

    use OptionsRequestAllowed;
}
