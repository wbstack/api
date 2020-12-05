<?php

namespace Tests\Routes\Admin\Invitation;

use Tests\TestCase;
use Tests\Routes\Traits\OptionsRequestAllowed;

class ListTest extends TestCase
{
    protected $route = 'admin/invitation/list';

    use OptionsRequestAllowed;
}
