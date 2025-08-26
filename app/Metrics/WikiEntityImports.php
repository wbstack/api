<?php

namespace App\Metrics;

use App\WikiEntityImport;
use App\WikiEntityImportStatus;
use LKDevelopment\HorizonPrometheusExporter\Contracts\Exporter;
use Prometheus\CollectorRegistry;

class WikiEntityImports implements Exporter {
    protected $pending;

    public function metrics(CollectorRegistry $collectorRegistry) {
        $this->pending = $collectorRegistry->getOrRegisterGauge(
            config('horizon-exporter.namespace'),
            'wiki_entity_imports_pending',
            'The number of pending Entity imports currently being processed.',
        );
    }

    public function collect() {
        // counters for failed / success are incremented in the HTTP controller
        $this->pending->set(
            WikiEntityImport::where(['status' => WikiEntityImportStatus::Pending])->count()
        );
    }
}
