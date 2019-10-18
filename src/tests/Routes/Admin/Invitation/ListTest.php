<?php

namespace Tests\Routes\Admin\Invitation;

use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;

class ListTest extends TestCase
{
    protected $route = 'admin/invitation/list';

    use OptionsRequestAllowed;
}
