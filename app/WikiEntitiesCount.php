<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WikiEntitiesCount extends Model
{
    use HasFactory;

    const FIELDS = [
        'items_count',
        'properties_count',
        'wiki_id'
    ];

    protected $fillable = self::FIELDS;

    protected $visible = self::FIELDS;

    protected $casts = [
        'items_count' => 'integer',
        'properties_count' => 'integer'
    ];
}
