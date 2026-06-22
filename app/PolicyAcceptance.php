<?php

namespace App;

use Carbon\CarbonImmutable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * This model uses a separate `accepted_at` property rather than renaming the default `created_at` property because:
 *   - it reduces confuses by remaining consistent with other models that use the default timestamps
 *   - `accepted_at` will be before `created_at` when backfilling the terms-of-use acceptances
 *
 * @property-read int $id
 * @property int $user_id
 * @property int $policy_id
 * @property CarbonImmutable $accepted_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
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
    // TODO: also create a factory (and seeder)?

    // define which attributes are mass assignable
    protected $fillable = [
        'user_id',
        'policy_id',
        // Don't allow `accepted_at` to be mass assigned? Most of the time this will be set to the current timestamp by the database.
        // 'accepted_at',
    ];

    protected function casts(): array {
        return [
            // cast `accepted_at` to a `CarbonImmutable` instance rather than a string
            'accepted_at' => 'immutable_datetime',

            // TODO: should we make Laravel use CarbonImmutable globally instead of casting in models?
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
