<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WikiLifecycleEvents extends Model
{
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
