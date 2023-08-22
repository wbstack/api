<?php

namespace App\Http\Controllers;

use App\WikiSiteStats;
use Illuminate\Http\Request;
use App\Wiki;
use App\Http\Resources\PublicWikiResource;

class PublicWikiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', null);
        if ($perPage !== null) {
            $perPage = intval($perPage);
        }

        $query = Wiki::query();

        $isFeatured = $request->query('is_featured', null);
        if ($isFeatured !== null) {
            $query = $query->where(['is_featured' => boolval($isFeatured)]);
        }

        $isActive = $request->query('is_active', null);
        if ($isActive !== null && boolval($isActive)) {
            $query = $query->whereRelation('wikiSiteStats', 'pages', '>', 0);
        }

        $sort = $request->query('sort', 'sitename');
        switch ($sort) {
        case 'sitename':
            $query = $query->orderBy(
                'sitename',
                $request->query('direction', 'asc')
            );
            break;
        case 'pages':
            $query = $query->orderBy(
                WikiSiteStats::query()->select('pages')->whereColumn('wiki_site_stats.wiki_id', 'wikis.id'),
                $request->query('direction', 'asc')
            );
            break;
        default:
            return response()->json(['message' => 'Sorting by '.$sort.' is not supported.'], 400);
        }

        return PublicWikiResource::collection($query->paginate($perPage));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        return response()->json(['message' => 'Method not allowed'], 405);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return new PublicWikiResource(Wiki::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        return response()->json(['message' => 'Method not allowed'], 405);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): \Illuminate\Http\JsonResponse
    {
        return response()->json(['message' => 'Method not allowed'], 405);
    }
}
