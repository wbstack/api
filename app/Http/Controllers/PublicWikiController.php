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
        $query = Wiki::query();

        $isFeatured = $request->query('is_featured', null);
        if ($isFeatured !== null) {
            $query = $query->where(['is_featured' => boolval($isFeatured)]);
        }

        $isActive = $request->query('is_active', null);
        if ($isActive !== null && boolval($isActive)) {
            $query = $query->whereRelation('wikiSiteStats', 'pages', '>', 0);
        }

        $direction = $request->query('direction', 'asc');
        if ($direction !== 'asc' && $direction !== 'desc') {
            return response()
                ->json(
                    ['message' => 'Direction '.$direction.' is not supported.'],
                    400
                );
        }

        $sort = $request->query('sort', 'sitename');
        switch ($sort) {
        case 'sitename':
            $query = $query->orderBy(
                'sitename',
                $direction
            );
            break;
        case 'pages':
            $query = $query->orderBy(
                WikiSiteStats::query()
                    ->select('pages')
                    ->whereColumn('wiki_site_stats.wiki_id', 'wikis.id'),
                $direction
            );
            break;
        default:
            return response()
                ->json(
                    ['message' => 'Sorting by '.$sort.' is not supported.'],
                    400
                );
        }

        $perPage = $request->query('per_page', null);
        if ($perPage !== null) {
            $perPage = intval($perPage);
        }
        return PublicWikiResource::collection($query->paginate($perPage));
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return new PublicWikiResource(Wiki::findOrFail($id));
    }
}
