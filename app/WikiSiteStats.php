<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
