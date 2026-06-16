<?php

namespace Tests\Metrics;

use App\Metrics\PendingQsBatches;
use LKDevelopment\HorizonPrometheusExporter\Repository\ExporterRepository;
use Tests\TestCase;

class PendingQsBatchesTest extends TestCase {
    public function testCanBeCreated() {
        ExporterRepository::load([PendingQsBatches::class]);

        self::assertNotNull(ExporterRepository::getRegistry());

        $gauge = ExporterRepository::getRegistry()->getGauge(config('horizon-exporter.namespace'), 'qs_batches_pending_batches');

        self::assertNotNull($gauge);
    }
}
