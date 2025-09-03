<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class QsCheckpoint extends Model {
    public const CHECKPOINT_ID = 0;

    const FIELDS = [
        'id',
        'checkpoint',
    ];

    protected $fillable = self::FIELDS;

    protected $visible = self::FIELDS;

    public static function init(int $value = 0): void {
        self::create([
            'id' => self::CHECKPOINT_ID,
            'checkpoint' => $value,
        ]);
    }

    public static function get(): int {
        $match = self::where(['id' => self::CHECKPOINT_ID])->first();
        if (!$match) {
            throw new ModelNotFoundException(
                'No QsCheckpoint found. Is your table properly initialized?'
            );
        }

        return $match->checkpoint;
    }

    public static function set(int $val): void {
        $match = self::where(['id' => self::CHECKPOINT_ID])->first();
        if (!$match) {
            throw new ModelNotFoundException(
                'No QsCheckpoint found. Is your table properly initialized?'
            );
        }
        $match->update(['checkpoint' => $val]);
    }
}
