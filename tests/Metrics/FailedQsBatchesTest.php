<?php

namespace Tests\Metrics;

use App\Metrics\FailedQsBatches;
use LKDevelopment\HorizonPrometheusExporter\Repository\ExporterRepository;
use Tests\TestCase;

class FailedQsBatchesTest extends TestCase {
    public function testCanBeCreated() {
        ExporterRepository::load([FailedQsBatches::class]);

        self::assertNotNull(ExporterRepository::getRegistry());

        $gauge = ExporterRepository::getRegistry()->getGauge(config('horizon-exporter.namespace'), 'qs_batches_failed_batches');

        self::assertNotNull($gauge);

    }
}
