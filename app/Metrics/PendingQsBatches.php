<?php


namespace App\Metrics;

use LKDevelopment\HorizonPrometheusExporter\Contracts\Exporter;
use Prometheus\CollectorRegistry;
use App\QsBatch;

class PendingQsBatches implements Exporter
{
    protected $gauge;

    public function metrics(CollectorRegistry $collectorRegistry)
    {
        $this->gauge = $collectorRegistry->getOrRegisterGauge(
            config('horizon-exporter.namespace'),
            'qs_batches_pending_batches',
            'The number of QueryService batches waiting to be processed',
        );
    }

    public function collect()
    {
        $numBatches = QsBatch::has('wiki')->where([
            'done' => 0,
            'failed' => 0,
        ])->count();
        $this->gauge->set($numBatches);
    }
}
