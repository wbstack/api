<?php

namespace App;

use App\Enums\ReviewSubmissionActionType;
use Carbon\CarbonImmutable;
use Database\Factories\ReviewSubmissionFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $wiki_id
 * @property string|null $additional_information
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, ReviewSubmissionAction> $actions
 * @property-read int|null $actions_count
 * @property-read ReviewSubmissionAction|null $latestAction
 * @property-read Wiki|null $wiki
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewSubmission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewSubmission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewSubmission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewSubmission whereAdditionalInformation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewSubmission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewSubmission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewSubmission whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewSubmission whereWikiId($value)
 * @method static \Database\Factories\ReviewSubmissionFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
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

    protected function casts(): array {
        return [
            // cast to `CarbonImmutable` until we default to using `CarbonImmutable` globally in T430656
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

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

    // TODO: this could be an Attribute Accessor method: https://laravel.com/framework/docs/11.x/eloquent-mutators#accessors-and-mutators
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
        return $this->latestAction->type;
    }
}
