<?php

namespace App\Http\Controllers;

use App\WikiEntityImportStatus;
use Illuminate\Http\Request;
use App\Wiki;
use App\WikiEntityImport;
use App\Jobs\WikiEntityImportDummyJob;
use Carbon\Carbon;

class WikiEntityImportController extends Controller
{
    public function get(Request $request): \Illuminate\Http\JsonResponse
    {
        $wiki = Wiki::find($request->input('wiki'));
        if (!$wiki) {
            abort(404, 'No such wiki');
        }

        $imports = $wiki->wikiEntityImports()->get();
        return response()->json(['data' => $imports]);
    }

    public function create(Request $request): \Illuminate\Http\JsonResponse
    {
        $wiki = Wiki::find($request->input('wiki'));
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
            'payload' => $request->all(),
        ]);

        dispatch(new WikiEntityImportDummyJob(
            wikiId: $wiki->id,
            sourceWikiUrl: $request->input('source_wiki_url'),
            importId: $import->id,
            entityIds: $request->input('entity_ids'),
        ));

        return response()->json(['data' => $import]);
    }

    public function update(Request $request): \Illuminate\Http\JsonResponse
    {
        // This route is not supposed have ACL middlewares in front as it is expected
        // to be called from backend services that are implicitly allowed
        // access right.
        $import = WikiEntityImport::find($request->input('wiki_entity_import'));
        if (!$import) {
            abort(404, 'No such import');
        }

        if (!WikiEntityImportStatus::tryFrom($request->input('status'))) {
            abort(400, 'Status '.$request->input('status').' is not allowed');
        }

        if ($import->status !== WikiEntityImportStatus::Pending) {
            abort(400, 'Import has to be pending if updated');
        }

        $import->update([
            'status' => $request->input('status'),
            'finished_at' => Carbon::now(),
        ]);

        return response()->json(['data' => $import]);
    }
}
