<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ActiveSuspension extends Model
{
    protected $fillable = [
        'since',
        'wiki_id'
    ];

    public function Wiki(): BelongsTo {
        return $this->belongsTo(Wiki::class);
    }
}
