<?php


namespace App\Metrics;

use LKDevelopment\HorizonPrometheusExporter\Contracts\Exporter;
use Prometheus\CollectorRegistry;
use App\QsBatch;

class FailedQsBatches implements Exporter
{
    protected $gauge;

    public function metrics(CollectorRegistry $collectorRegistry)
    {
        $this->gauge = $collectorRegistry->getOrRegisterGauge(
            config('horizon-exporter.namespace'),
            'qs_batches_failed_batches',
            'The number of QueryService batches marked as failed',
        );
    }

    public function collect()
    {
        $numBatches = QsBatch::has('wiki')->where([
            'failed' => 1,
        ])->count();
        $this->gauge->set($numBatches);
    }
}
