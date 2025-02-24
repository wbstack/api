<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// This class is supposed to be a 1:1 representation of the data returned
// by calling the MediaWiki API with parameters
// `?action=query&meta=siteinfo&siprop=statistics`
// When adding additional data about a wiki that comes from a different
// source, consider storing it elsewhere.
class WikiSiteStats extends Model
{
    use HasFactory;

    const FIELDS = [
        'pages',
        'articles',
        'edits',
        'images',
        'users',
        'activeusers',
        'admins',
        'jobs',
        'cirrussearch-article-words'
    ];

    protected $fillable = self::FIELDS;

    protected $visible = self::FIELDS;
}
