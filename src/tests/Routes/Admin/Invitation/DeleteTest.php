<?php

namespace Tests\Routes\Admin\Invitation;

use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    protected $route = 'admin/invitation/delete';

    use OptionsRequestAllowed;
}
