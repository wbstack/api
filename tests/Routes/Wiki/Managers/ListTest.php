<?php

namespace Tests\Routes\Wiki\Managers;

use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;

class ListTest extends TestCase
{
    protected $route = 'wiki/managers/list';

    use OptionsRequestAllowed;
}
