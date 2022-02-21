<?php

namespace Tests\Jobs;

use Tests\TestCase;
use App\Helper\DomainValidator;
use Illuminate\Support\Facades\Config;
use App\Rules\ForbiddenSubdomainRule;

class DomainValidatorTest extends TestCase
{
    public function testValidatorUsesOldValidation()
    {
        $sut = new DomainValidator('.wbaas.localhost', []);
        $validator = $sut->validate('derp');
        $this->assertCount(1, $validator->errors());
    }
    public function testValidatorUsesList()
    {
        $sut = new DomainValidator('.wbaas.localhost', [
            new ForbiddenSubdomainRule( ['long-terrible-word'], '.wbaas.localhost' )
        ]);
        $validator = $sut->validate('long-terrible-word.wbaas.localhost');
        $this->assertCount(1, $validator->errors());
        $this->assertEquals(ForbiddenSubdomainRule::ERROR_MESSAGE, $validator->errors()->get('domain')[0]);
    }

    public function testAppValidator()
    {
        Config::set('wbstack.subdomain_suffix', '.wbaas.localhost');
        $sut = $this->app->make(DomainValidator::class);
        $validator = $sut->validate('statistics.wbaas.localhost');
        $this->assertCount(1, $validator->errors());
        $this->assertEquals(ForbiddenSubdomainRule::ERROR_MESSAGE, $validator->errors()->get('domain')[0]);
    }
}
