<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TermsOfUseVersion extends Model {
    use HasFactory;

    protected $table = 'tou_versions';

    const FIELDS = [
        'version',
        'active',
        'acceptance_deadline',
        'content',
    ];

    protected $fillable = self::FIELDS;

    protected $visible = self::FIELDS;

    protected $casts = [
        'version' => 'string',
        'active' => 'boolean',
        'acceptance_deadline' => 'datetime',
        'content' => 'string',
    ];

    public static function latestVersion(): ?self {
        return self::query()->where('active', true)->first();
    }
}
