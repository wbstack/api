<?php

declare(strict_types=1);

namespace App;

use App\Enums\WikiSuspensionReason;
use Carbon\CarbonImmutable;
use Database\Factories\WikiSuspensionFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $actor_user_id
 * @property int $wiki_id
 * @property int|null $review_submission_action_id
 * @property CarbonImmutable $active_from
 * @property WikiSuspensionReason $reason
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User|null $actor
 * @property-read ReviewSubmissionAction|null $reviewSubmissionAction
 * @property-read Wiki|null $wiki
 *
 * @method static \Database\Factories\WikiSuspensionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiSuspension newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiSuspension newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiSuspension query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiSuspension whereActiveFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiSuspension whereActorUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiSuspension whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiSuspension whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiSuspension whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiSuspension whereReviewSubmissionActionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiSuspension whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WikiSuspension whereWikiId($value)
 *
 * @mixin \Eloquent
 */
class WikiSuspension extends Model {
    /** @use HasFactory<WikiSuspensionFactory> */
    use HasFactory;

    protected function casts() {
        return [
            'reason' => WikiSuspensionReason::class,
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

    protected function active(): Attribute {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): bool => CarbonImmutable::now()->gte($attributes['active_from']),
        );
    }

    protected $appends = ['active'];
}
