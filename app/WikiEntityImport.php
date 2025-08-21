<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WikiEntityImport extends Model {
    use HasFactory;

    const FIELDS = [
        'status',
        'started_at',
        'finished_at',
        'payload',
    ];

    protected $fillable = self::FIELDS;

    protected $visible = self::FIELDS;

    protected $casts = [
        'status' => WikiEntityImportStatus::class,
        'payload' => 'array',
    ];
}
