<?php

namespace App\Tests\Routes\Wiki\Managers;

use App\Tests\TestCase;
use App\Tests\Routes\Traits\OptionsRequestAllowed;

class ListTest extends TestCase {

    protected $route = 'wiki/managers/list';

    use OptionsRequestAllowed;

}
