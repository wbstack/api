<?php

namespace Tests\Routes\Wiki\Managers;

use Tests\TestCase;
use Tests\Routes\Traits\OptionsRequestAllowed;

class MineTest extends TestCase
{
    protected $route = 'wiki/mine';

    use OptionsRequestAllowed;
}
