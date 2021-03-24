<?php

namespace Tests\Routes\Wiki\Managers;

use Tests\TestCase;
use Tests\Routes\Traits\OptionsRequestAllowed;
use Tests\Routes\Traits\PostRequestNeedAuthentication;

class SettingUpdateTest extends TestCase
{
    protected $route = 'wiki/setting/foo/update';

    use OptionsRequestAllowed;
    use PostRequestNeedAuthentication;
}
