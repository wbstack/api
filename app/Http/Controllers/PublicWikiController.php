<?php

namespace App\Http\Controllers;

use App\WikiSiteStats;
use Illuminate\Http\Request;
use App\Wiki;
use App\Http\Resources\PublicWikiResource;

class PublicWikiController extends Controller
{
    private static $defaultParams = [
        'sort' => 'sitename',
        'direction' => 'asc'
    ];
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'sort' => 'in:sitename,pages',
            'direction' => 'in:desc,asc',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'per_page' => 'numeric',
            'page' => 'numeric'
        ]);

        $params = array_merge(self::$defaultParams, $request->input());
        $query = Wiki::query();

        if (array_key_exists('is_featured', $params)) {
            $query = $query->where([
                'is_featured' => boolval($params['is_featured'])
            ]);
        }

        if (array_key_exists('is_active', $params) && $params['is_active']) {
            $query = $query->whereRelation('wikiSiteStats', 'pages', '>', 0);
        }

        switch ($params['sort']) {
        case 'sitename':
            $query = $query->orderBy(
                'sitename',
                $params['direction']
            );
            break;
        case 'pages':
            $query = $query->orderBy(
                WikiSiteStats::query()
                    ->select('pages')
                    ->whereColumn('wiki_site_stats.wiki_id', 'wikis.id'),
                $params['direction']
            );
            break;
        }

        $perPage = null;
        if (array_key_exists('per_page', $params)) {
            $perPage = intval($params['per_page']);
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
