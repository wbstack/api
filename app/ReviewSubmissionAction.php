<?php

namespace App;

use App\Enums\ReviewSubmissionActionType;
use Database\Factories\ReviewSubmissionActionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $review_submission_id
 * @property int $user_id
 * @property string $actor_role
 * @property ReviewSubmissionActionType $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ReviewSubmission $reviewSubmission
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewSubmissionAction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewSubmissionAction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewSubmissionAction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewSubmissionAction whereActorRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewSubmissionAction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewSubmissionAction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewSubmissionAction whereReviewSubmissionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewSubmissionAction whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewSubmissionAction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewSubmissionAction whereUserId($value)
 * @method static \Database\Factories\ReviewSubmissionActionFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class ReviewSubmissionAction extends Model {
    /** @use HasFactory<ReviewSubmissionActionFactory> */
    use HasFactory;

    protected function casts(): array {
        return [
            'type' => ReviewSubmissionActionType::class,
            // cast to `CarbonImmutable` until we default to using `CarbonImmutable` globally in T430656
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /**
     * Get the review submission to which this action belongs.
     */
    public function reviewSubmission(): BelongsTo {
        return $this->belongsTo(ReviewSubmission::class);
    }
}
