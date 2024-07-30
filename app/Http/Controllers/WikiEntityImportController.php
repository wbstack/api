<?php

namespace App\Http\Controllers;

use App\WikiEntityImportStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Wiki;
use App\WikiEntityImport;
use App\Jobs\WikiEntityImportJob;
use Carbon\Carbon;
use LKDevelopment\HorizonPrometheusExporter\Repository\ExporterRepository;
use Prometheus\Counter;

class WikiEntityImportController extends Controller
{
    private Counter $successfulCounter;
    private Counter $failedCounter;

    public function __construct()
    {
        ExporterRepository::load();
        $this->successfulCounter = ExporterRepository::getRegistry()->getCounter(
            config('horizon-exporter.namespace'),
            'wiki_entity_imports_successful',
        );
        $this->failedCounter = ExporterRepository::getRegistry()->getCounter(
            config('horizon-exporter.namespace'),
            'wiki_entity_imports_failed',
        );
    }
    public function get(Request $request): \Illuminate\Http\JsonResponse
    {
        $validatedInput = $request->validate([
            'wiki' => ['required', 'integer'],
        ]);
        $wiki = Wiki::find($validatedInput['wiki']);
        if (!$wiki) {
            abort(404, 'No such wiki');
        }

        $imports = $wiki->wikiEntityImports()->get();
        return response()->json(['data' => $imports]);
    }

    public function create(Request $request): \Illuminate\Http\JsonResponse
    {
        $validatedInput = $request->validate([
            'wiki' => ['required', 'integer'],
            'source_wiki_url' => ['required', 'url'],
            'entity_ids' => ['required', 'string', function (string $attr, mixed $value, \Closure $fail) {
                $chunks = explode(',', $value);
                foreach ($chunks as $chunk) {
                    if (!preg_match("/^[A-Z]\d+(@\d+)?$/", $chunk)) {
                        $fail("Received unexpected input '{$chunk}' cannot continue.");
                    }
                }
            }],
        ]);

        $wiki = Wiki::find($validatedInput['wiki']);
        if (!$wiki) {
            abort(404, 'No such wiki');
        }

        $imports = $wiki->wikiEntityImports()->get();
        foreach ($imports as $import) {
            if ($import->status === WikiEntityImportStatus::Success) {
                return response()
                    ->json(['error' => 'Wiki "'.$wiki->domain.'" already has performed a successful entity import'])
                    ->setStatusCode(400);
            }
            if ($import->status === WikiEntityImportStatus::Pending) {
                return response()
                    ->json(['error' => 'Wiki "'.$wiki->domain.'" currently has a pending entity import'])
                    ->setStatusCode(400);
            }
        }

        $import = $wiki->wikiEntityImports()->create([
            'status' => WikiEntityImportStatus::Pending,
            'started_at' => Carbon::now(),
            'payload' => $validatedInput,
        ]);

        dispatch(new WikiEntityImportJob(
            wikiId: $wiki->id,
            sourceWikiUrl: $validatedInput['source_wiki_url'],
            importId: $import->id,
            entityIds: explode(',', $validatedInput['entity_ids']),
        ));

        return response()->json(['data' => $import]);
    }

    public function update(Request $request): \Illuminate\Http\JsonResponse
    {
        // This route is not supposed have ACL middlewares in front as it is expected
        // to be called from backend services that are implicitly allowed
        // access right.
        $validatedInput = $request->validate([
            'wiki_entity_import' => ['required', 'integer'],
            'status' => ['required', Rule::in([WikiEntityImportStatus::Failed->value, WikiEntityImportStatus::Success->value])],
        ]);

        $import = WikiEntityImport::find($validatedInput['wiki_entity_import']);
        if (!$import) {
            abort(404, 'No such import');
        }

        if ($import->status !== WikiEntityImportStatus::Pending) {
            abort(400, 'Import has to be pending if updated');
        }

        if ($validatedInput['status'] === WikiEntityImportStatus::Failed->value) {
            $this->failedCounter->inc();
        }
        if ($validatedInput['status'] === WikiEntityImportStatus::Success->value) {
            $this->successfulCounter->inc();
        }

        $import->update([
            'status' => $validatedInput['status'],
            'finished_at' => Carbon::now(),
        ]);

        return response()->json(['data' => $import]);
    }
}
