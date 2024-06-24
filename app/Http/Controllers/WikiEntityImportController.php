<?php

namespace App\Http\Controllers;

use App\WikiEntityImportStatus;
use Illuminate\Http\Request;
use App\Wiki;
use App\WikiEntityImport;
use Carbon\Carbon;

class WikiEntityImportController extends Controller
{
    public function get(Request $request): \Illuminate\Http\JsonResponse
    {
        $wiki = Wiki::find($request->input('wiki_id'));
        if (!$wiki) {
            return response()->json(['error' => 'Not Found'])->setStatusCode(404);
        }
        $imports = $wiki->wikiEntityImports()->get();
        return response()->json(['data' => $imports]);
    }

    public function create(Request $request): \Illuminate\Http\JsonResponse
    {
        $wiki = Wiki::find($request->input('wiki_id'));
        if (!$wiki) {
            return response()->json(['error' => 'Not Found'])->setStatusCode(404);
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
        $import = WikiEntityImport::create([
            'wiki_id' => $wiki->id,
            'status' => WikiEntityImportStatus::Pending,
            'started_at' => Carbon::now(),
        ]);
        return response()->json(['data' => $import]);
    }

    public function update(Request $request): \Illuminate\Http\JsonResponse
    {
        $import = WikiEntityImport::find($request->input('wiki_entity_import_id'));
        $import->update([
            'status' => $request->input('status'),
            'finished_at' => Carbon::now(),
        ]);
        return response()->json(['data' => $import]);
    }
}
