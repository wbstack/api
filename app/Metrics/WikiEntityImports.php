<?php


namespace App\Metrics;

use LKDevelopment\HorizonPrometheusExporter\Contracts\Exporter;
use Prometheus\CollectorRegistry;
use App\WikiEntityImport;
use App\WikiEntityImportStatus;

class WikiEntityImports implements Exporter
{
    protected $pending;
    protected $successful;
    protected $failed;

    public function metrics(CollectorRegistry $collectorRegistry)
    {
        $this->pending = $collectorRegistry->getOrRegisterGauge(
            config('horizon-exporter.namespace'),
            'wiki_entity_imports_pending',
            'The number of pending Entity imports currently being processed.',
        );
        $this->successful = $collectorRegistry->getOrRegisterCounter(
            config('horizon-exporter.namespace'),
            'wiki_entity_imports_successful',
            'The number of successful Entity import records.',
        );
        $this->failed = $collectorRegistry->getOrRegisterCounter(
            config('horizon-exporter.namespace'),
            'wiki_entity_imports_failed',
            'The number of failed Entity import records.',
        );
    }


    public function collect()
    {
        // counters for failed / success are incremented in the HTTP controller
        $this->pending->set(
            WikiEntityImport::where(['status' => WikiEntityImportStatus::Pending])->count()
        );
    }
}
