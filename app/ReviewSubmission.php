<?php

declare(strict_types=1);

namespace App;

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

    /**
     * The current "state" of a review submission (`$submission->latestAction->type`) is
     * frequently required. Always eager loading the latestAction relationship avoids additional
     * queries and protects against N+1 query issues when processing multiple submissions.
     *
     * Use one of the `->without*()` methods to prevent these from being eager loaded.
     *
     * @var list<string>
     */
    protected $with = [
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
}
