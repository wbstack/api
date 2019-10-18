<?php

namespace Tests\Routes\Wiki\Managers;

use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\TestCase;

class DetailsTest extends TestCase
{
    protected $route = 'wiki/details';

    use OptionsRequestAllowed;
}
