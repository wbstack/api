<?php

namespace App;

use Carbon\CarbonImmutable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * This model uses a separate `accepted_at` property rather than renaming the default `created_at` property because:
 *   - it remains consistent with other models that use the default timestamps
 *   - `accepted_at` will be before `created_at` when backfilling the terms-of-use acceptances
 *
 * @property int $id
 * @property int $user_id
 * @property int $policy_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable $accepted_at
 *
 * @method static Builder<static>|PolicyAcceptance newModelQuery()
 * @method static Builder<static>|PolicyAcceptance newQuery()
 * @method static Builder<static>|PolicyAcceptance query()
 * @method static Builder<static>|PolicyAcceptance whereAcceptedAt($value)
 * @method static Builder<static>|PolicyAcceptance whereCreatedAt($value)
 * @method static Builder<static>|PolicyAcceptance whereId($value)
 * @method static Builder<static>|PolicyAcceptance wherePolicyId($value)
 * @method static Builder<static>|PolicyAcceptance whereUpdatedAt($value)
 * @method static Builder<static>|PolicyAcceptance whereUserId($value)
 *
 * @mixin Eloquent
 */
class PolicyAcceptance extends Model {
    protected $fillable = [
        'user_id',
        'policy_id',
        'accepted_at',
    ];

    protected function casts(): array {
        return [
            // cast `accepted_at` to a `CarbonImmutable` instance rather than a string
            'accepted_at' => 'immutable_datetime',

            // cast to `CarbonImmutable` until we default to using `CarbonImmutable` globally in T430656
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
