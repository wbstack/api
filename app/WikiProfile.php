<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WikiProfile extends Model {
    use HasFactory;

    protected $fillable = [
        'wiki_id',
        'purpose',
        'purpose_other',
        'audience',
        'audience_other',
        'temporality',
        'temporality_other',
    ];

    public function wiki(): BelongsTo {
        return $this->belongsTo(Wiki::class);
    }
}
