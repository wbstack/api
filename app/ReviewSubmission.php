<?php

namespace App;

use App\Enums\ReviewSubmissionActionType;
use Database\Factories\ReviewSubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReviewSubmission extends Model {
    /** @use HasFactory<ReviewSubmissionFactory> */
    use HasFactory;

    protected $fillable = [
        'wiki_id',
        'additional_information',
    ];

    // TODO: verify this is beneficial more times than it's not
    /**
     * The current "state" of a review submission (`$submission->latestAction->type`) is
     * frequently required. Always eager loading the latestAction relationship avoids additional
     * queries and protects against N+1 query issues when processing multiple submissions.
     *
     * @var list<string>
     */
    protected $with = [
        // TODO: should this be all actions rather than latest? The overhead will be minimal between the two.
        'latestAction',
    ];

    /**
     * Get the Wiki this review submission is for.
     *
     * Access the related actions through the `wiki` property, or use
     * the relationship directly when further querying is required.
     */
    public function wiki(): BelongsTo {
        return $this->belongsTo(Wiki::class);
    }

    /**
     * Get all actions for this review submission.
     *
     * Access the related actions through the `actions` property, or use the
     * relationship directly when further querying is required.
     */
    public function actions(): HasMany {
        return $this->hasMany(ReviewSubmissionAction::class);
    }

    /**
     * Get the most recent action for this review submission.
     *
     * The type of the latest action represents the current "state" of the submission.
     *
     * Access the action through the `latestAction` property, or use the
     * relationship directly when further querying is required.
     */
    public function latestAction(): HasOne {
        return $this->hasOne(ReviewSubmissionAction::class)->latestOfMany();
    }

    // TODO: should this return null or make a ReviewSubmissionActionType::NONE type in application code only?
    /**
     * Get the latest action type of the review submission.
     *
     * The type of the latest action represents the current "state" of the submission.
     */
    public function latestActionType(): ReviewSubmissionActionType {
        // TODO: There should always be at least 1 action as when the review is submitted it's type is 'submitted'.
        // TODO: $this->latestAction should never return null.
        // TODO: Should we defend against that assumption?
        // TODO: Make sure that a ReviewSubmissionAction is always created when the ReviewSubmission is?
        return $this->latestAction->state;
    }
}
