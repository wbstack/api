<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewSubmission extends Model
{
    public function Wiki(): BelongsTo {
        return $this->belongsTo(Wiki::class);
    }
}
