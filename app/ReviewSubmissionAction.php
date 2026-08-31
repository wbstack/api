<?php

namespace App;

use App\Enums\ReviewSubmissionState;
use Database\Factories\ReviewSubmissionActionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewSubmissionAction extends Model {
    /** @use HasFactory<ReviewSubmissionActionFactory> */
    use HasFactory;

    /**
     * Get the review submission to which this event belongs.
     */
    public function reviewSubmission(): BelongsTo {
        return $this->belongsTo(ReviewSubmission::class);
    }

    protected function casts(): array {
        return [
            'state' => ReviewSubmissionState::class,
        ];
    }
}
