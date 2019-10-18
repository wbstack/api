<?php

namespace Tests\Routes\Admin\Invitation;

use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;

class CreateTest extends TestCase
{
    protected $route = 'admin/invitation/create';

    use OptionsRequestAllowed;
}
