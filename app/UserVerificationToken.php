<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\UserVerificationToken.
 *
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \App\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserVerificationToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserVerificationToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserVerificationToken query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserVerificationToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserVerificationToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserVerificationToken whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserVerificationToken whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\UserVerificationToken whereUserId($value)
 *
 * @mixin \Eloquent
 */
class UserVerificationToken extends Model {
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'token',
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}
