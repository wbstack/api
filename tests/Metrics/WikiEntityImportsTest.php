<?php

namespace Tests\Metrics;

use App\Metrics\WikiEntityImports;
use LKDevelopment\HorizonPrometheusExporter\Repository\ExporterRepository;
use Tests\TestCase;

class WikiEntityImportsTest extends TestCase {
    public function testCanBeCreated() {
        ExporterRepository::load([WikiEntityImports::class]);

        self::assertNotNull(ExporterRepository::getRegistry());

        $gauge = ExporterRepository::getRegistry()->getGauge(config('horizon-exporter.namespace'), 'wiki_entity_imports_pending');

        self::assertNotNull($gauge);
    }
}
