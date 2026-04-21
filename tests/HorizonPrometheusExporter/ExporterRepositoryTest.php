<?php

namespace Tests\HorizonPrometheusExporter;

use LKDevelopment\HorizonPrometheusExporter\Repository\ExporterRepository;
use Prometheus\CollectorRegistry;
use Tests\TestCase;

class ExporterRepositoryTest extends TestCase {
    public function testCanBeCreated() {
        // if nothing is passed to load it reads the config
        ExporterRepository::load();
        $registry = ExporterRepository::getRegistry();
        $this->assertInstanceOf(CollectorRegistry::class, $registry);
    }
}
