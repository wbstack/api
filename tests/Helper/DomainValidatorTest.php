<?php

namespace Tests\Jobs;

use App\Helper\DomainValidator;
use App\Rules\ForbiddenSubdomainRule;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DomainValidatorTest extends TestCase {
    public function testValidatorUsesOldValidation() {
        $sut = new DomainValidator('.wbaas.localhost', []);
        $validator = $sut->getValidator('derp');
        $this->assertCount(1, $validator->errors());
    }

    public function testValidatorUsesList() {
        $sut = new DomainValidator('.wbaas.localhost', [
            new ForbiddenSubdomainRule(['long-terrible-word'], '.wbaas.localhost'),
        ]);
        $validator = $sut->getValidator('long-terrible-word.wbaas.localhost');
        $this->assertCount(1, $validator->errors());
        $this->assertEquals(ForbiddenSubdomainRule::ERROR_MESSAGE, $validator->errors()->get('domain')[0]);
    }

    public function testAppValidator() {
        Config::set('wbstack.subdomain_suffix', '.wbaas.localhost');
        $sut = $this->app->make(DomainValidator::class);
        $validator = $sut->getValidator('statistics.wbaas.localhost');
        $this->assertCount(1, $validator->errors());
        $this->assertEquals(ForbiddenSubdomainRule::ERROR_MESSAGE, $validator->errors()->get('domain')[0]);
    }
}
