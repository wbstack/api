<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// This class is supposed to contain information about certain events
// in a wiki's lifecycle, e.g. the time of the last edit. Sources for these
// points in time can be chosen as needed.
class WikiLifecycleEvents extends Model {
    use HasFactory;

    const FIELDS = [
        'first_edited',
        'last_edited',
    ];

    protected $fillable = self::FIELDS;

    protected $visible = self::FIELDS;

    protected $casts = [
        'first_edited' => 'datetime',
        'last_edited' => 'datetime',
    ];
}
