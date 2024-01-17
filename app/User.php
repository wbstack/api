<?php

namespace App;

use App\Notifications\ResetPasswordNotification;
use http\Exception\RuntimeException;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * App\User.
 *
 * @property int $id
 * @property string $email
 * @property string $password
 * @property string|null $remember_token
 * @property int $verified
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $stripe_id
 * @property string|null $card_brand
 * @property string|null $card_last_four
 * @property string|null $trial_ends_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravel\Passport\Client[] $clients
 * @property-read int|null $clients_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Wiki[] $managesWikis
 * @property-read int|null $manages_wikis_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravel\Passport\Token[] $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereCardBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereCardLastFour($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereStripeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereTrialEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereVerified($value)
 * @mixin \Eloquent
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, Notifiable, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email', 'password', 'verified',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'stripe_id',
        'trial_ends_at',
        'card_brand',
        'card_last_four',
    ];

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     *
     * @psalm-return \Illuminate\Database\Eloquent\Relations\BelongsToMany<Wiki>
     */
    public function managesWikis(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Wiki::class, 'wiki_managers');
    }

    public function hasVerifiedEmail()
    {
        return (bool) $this->verified;
    }

    public function markEmailAsVerified()
    {
        $this->verified = 1;

        return true;
    }

    public function sendEmailVerificationNotification()
    {
        // This is required by the MustVerifyEmail interface that we use for middle ware.
        // But we currently send our emails via a different means, so we havn't implemented this..

        // We can not throw an exception here as this is still called! (even if we don't use it)
        // https://github.com/addshore/wbstack/issues/120
        //throw new RuntimeException('Not yet implemented');
    }

    public function getEmailForVerification()
    {
        return $this->email;
    }
	/**
	 * Convert the model instance to an array.
	 *
	 * @return array
	 */
	function toArray() {
		return parent::toArray();
	}

	/**
	 * Determine if the given attribute exists.
	 *
	 * @param mixed $offset
	 *
	 * @return bool
	 */
	function offsetExists($offset): bool {
		return parent::offsetExists($offset);
	}

	/**
	 * Get the value for a given offset.
	 *
	 * @param mixed $offset
	 *
	 * @return mixed
	 */
	function offsetGet($offset): mixed {
		return parent::offsetGet($offset);
	}

	/**
	 * Set the value for a given offset.
	 *
	 * @param mixed $offset
	 * @param mixed $value
	 *
	 * @return void
	 */
	function offsetSet($offset, $value): void {
		parent::offsetSet($offset, $value);
	}

	/**
	 * Unset the value for a given offset.
	 *
	 * @param mixed $offset
	 *
	 * @return void
	 */
	function offsetUnset($offset): void {
		parent::offsetUnset($offset);
	}

	/**
	 * Get the broadcast channel route definition that is associated with the given entity.
	 *
	 * @return string
	 */
	function broadcastChannelRoute() {
		return parent::broadcastChannelRoute();
	}

	/**
	 * Get the broadcast channel name that is associated with the given entity.
	 *
	 * @return string
	 */
	function broadcastChannel(): string {
		return parent::broadcastChannel();
	}

	/**
	 * Convert the model instance to JSON.
	 *
	 * @param int $options
	 *
	 * @return string
	 */
	function toJson($options = 0): string {
		return parent::toJson($options);
	}

	/**
	 * Convert the object into something JSON serializable.
	 *
	 * @return mixed
	 */
	function jsonSerialize(): mixed {
		return parent::jsonSerialize();
	}

	/**
	 * Get the queueable identity for the entity.
	 *
	 * @return mixed
	 */
	function getQueueableId() {
		return parent::getQueueableId();
	}

	/**
	 * Get the queueable relationships for the entity.
	 *
	 * @return array
	 */
	function getQueueableRelations() {
		return parent::getQueueableRelations();
	}

	/**
	 * Get the queueable connection for the entity.
	 *
	 * @return null|string
	 */
	function getQueueableConnection() {
		return parent::getQueueableConnection();
	}

	/**
	 * Get the value of the model's route key.
	 *
	 * @return mixed
	 */
	function getRouteKey() {
		return parent::getRouteKey();
	}

	/**
	 * Get the route key for the model.
	 *
	 * @return string
	 */
	function getRouteKeyName() {
		return parent::getRouteKeyName();
	}

	/**
	 * Retrieve the model for a bound value.
	 *
	 * @param mixed $value
	 * @param null|string $field
	 *
	 * @return \Illuminate\Database\Eloquent\Model|null
	 */
	function resolveRouteBinding($value, $field = null) {
		return parent::resolveRouteBinding($value, $field);
	}

	/**
	 * Retrieve the child model for a bound value.
	 *
	 * @param string $childType
	 * @param mixed $value
	 * @param null|string $field
	 *
	 * @return \Illuminate\Database\Eloquent\Model|null
	 */
	function resolveChildRouteBinding($childType, $value, $field) {
		return parent::resolveChildRouteBinding($childType, $value, $field);
	}
}
