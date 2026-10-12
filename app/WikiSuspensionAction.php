<?php

declare(strict_types=1);

namespace App;

use App\Enums\WikiSuspensionActionReason;
use App\Enums\WikiSuspensionActionType;
use Carbon\CarbonImmutable;
use Database\Factories\WikiSuspensionActionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property WikiSuspensionActionType $suspension_type
 * @property int|null $actor_user_id
 * @property int $wiki_id
 * @property int|null $review_submission_action_id
 * @property CarbonImmutable $active_from
 * @property WikiSuspensionActionReason $reason
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User|null $actor
 * @property-read ReviewSubmissionAction|null $reviewSubmissionAction
 * @property-read Wiki|null $wiki
 *
 * @method static \Database\Factories\WikiSuspensionActionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiSuspensionAction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiSuspensionAction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiSuspensionAction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiSuspensionAction whereActiveFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiSuspensionAction whereActorUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiSuspensionAction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiSuspensionAction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiSuspensionAction whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiSuspensionAction whereReviewSubmissionActionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiSuspensionAction whereSuspensionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiSuspensionAction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiSuspensionAction whereWikiId($value)
 *
 * @mixin \Eloquent
 */
class WikiSuspensionAction extends Model {
    /** @use HasFactory<WikiSuspensionActionFactory> */
    use HasFactory;

    protected function casts() {
        return [
            'suspension_type' => WikiSuspensionActionType::class,
            'reason' => WikiSuspensionActionReason::class,
            // cast to `CarbonImmutable` until we default to using `CarbonImmutable` globally in T430656
            'active_from' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    public function actor(): BelongsTo {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function reviewSubmissionAction(): BelongsTo {
        return $this->belongsTo(ReviewSubmissionAction::class);
    }

    public function wiki(): BelongsTo {
        return $this->belongsTo(Wiki::class);
    }
}
