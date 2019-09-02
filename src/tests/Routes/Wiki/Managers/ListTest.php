<?php

namespace Tests\Routes\Wiki\Managers;

use Tests\TestCase;
use Tests\Routes\Traits\OptionsRequestAllowed;

class ListTest extends TestCase
{
    protected $route = 'wiki/managers/list';

    use OptionsRequestAllowed;
}
