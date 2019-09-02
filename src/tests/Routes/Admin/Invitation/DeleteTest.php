<?php

namespace Tests\Routes\Admin\Invitation;

use Tests\TestCase;
use Tests\Routes\Traits\OptionsRequestAllowed;

class DeleteTest extends TestCase
{
    protected $route = 'admin/invitation/delete';

    use OptionsRequestAllowed;
}
