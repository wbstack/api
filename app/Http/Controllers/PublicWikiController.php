<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Wiki;
use App\Http\Resources\PublicWikiResource;
use App\Http\Resources\PublicWikiCollection;

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

        return new PublicWikiCollection($query->paginate($perPage));
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