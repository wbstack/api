<?php

namespace Tests\Commands;

use App\Wiki;
use App\QueryserviceNamespace;
use App\WikiSetting;
use App\Jobs\TemporaryDummyJob;
use Tests\TestCase;
use Illuminate\Support\Facades\Bus;

class RebuildQueryserviceDataTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();
        Wiki::query()->delete();
        WikiSetting::query()->delete();
        QueryserviceNamespace::query()->delete();
    }

    public function tearDown(): void
    {
        Wiki::query()->delete();
        WikiSetting::query()->delete();
        QueryserviceNamespace::query()->delete();
        parent::tearDown();
    }

    public function testEmpty()
    {
        Bus::fake();
        $this->artisan('wbs-qs:rebuild')->assertExitCode(0);
        Bus::assertNotDispatched(TemporaryDummyJob::class);
    }
}
