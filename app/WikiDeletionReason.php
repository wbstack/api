<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// This class holds the reason why a user delete a wiki
class WikiDeletionReason extends Model
{
    use HasFactory;

    const FIELDS = [
        'deletion_reason',
    ];

    protected $fillable = self::FIELDS;

    protected $visible = self::FIELDS;
}
