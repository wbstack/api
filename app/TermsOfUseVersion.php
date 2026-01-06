<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Bug: T401165 https://phabricator.wikimedia.org/T401165
 * Be mindful that multiple ToU versions may exist over time,
 * but only one should be active at a time.
 */
class TermsOfUseVersion extends Model {
    use HasFactory;

    protected $table = 'tou_versions';

    const FIELDS = [
        'version',
        'active',
    ];

    protected $fillable = self::FIELDS;

    protected $visible = self::FIELDS;

    protected $casts = [
        'version' => 'string',
        'active' => 'boolean',
    ];

    /**
     * Get the active ToU version.
     */
    public static function latestActiveVersion(): ?self {
        return self::query()->where('active', true)->first();
    }

    /**
     * Ensure only one ToU version remains active after any save operation.
     */
    protected static function booted(): void {
        static::saving(function (self $model): void {
            if (!$model->active) {
                return;
            }

            self::query()
                ->where('version', '!=', $model->version)
                ->where('active', true)
                ->update(['active' => false]);
        });
    }
}
