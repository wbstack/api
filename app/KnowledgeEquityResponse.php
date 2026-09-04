<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeEquityResponse extends Model {
    use HasFactory;

    protected $fillable = [
        'wiki_id',
        'selectedOption',
        'freeTextResponse',
    ];

    public function wiki(): BelongsTo {
        return $this->belongsTo(Wiki::class);
    }
}
