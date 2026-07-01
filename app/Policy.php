<?php

namespace App;

use Carbon\CarbonImmutable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $policy_type
 * @property CarbonImmutable|null $active_from
 * @property string $content_vue_file
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 *
 * @method static Builder<static>|Policy newModelQuery()
 * @method static Builder<static>|Policy newQuery()
 * @method static Builder<static>|Policy query()
 * @method static Builder<static>|Policy whereActiveFrom($value)
 * @method static Builder<static>|Policy whereContentVueFile($value)
 * @method static Builder<static>|Policy whereCreatedAt($value)
 * @method static Builder<static>|Policy whereId($value)
 * @method static Builder<static>|Policy wherePolicyType($value)
 * @method static Builder<static>|Policy whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class Policy extends Model {
    // define which attributes are mass assignable
    protected $fillable = [
        'policy_type',
        'active_from',
        'content_vue_file',
    ];

    // define the default value of model attributes when a new instance is created
    protected $attributes = [
        'active_from' => null,
    ];

    protected function casts(): array {
        return [
            // cast `active_from` to a `CarbonImmutable` instance rather than a string
            'active_from' => 'immutable_date',

            // cast to `CarbonImmutable` until we default to using `CarbonImmutable` globally in T430656
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
