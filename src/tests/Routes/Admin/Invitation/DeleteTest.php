<?php

namespace App\Tests\Routes\Admin\Invitation;

use App\Tests\TestCase;
use App\Tests\Routes\Traits\OptionsRequestAllowed;

class DeleteTest extends TestCase {

    protected $route = 'admin/invitation/delete';

    use OptionsRequestAllowed;

}
