<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * App\WikiManager.
 *
 * @property int $id
 * @property int $user_id
 * @property int $wiki_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\User $user
 * @property-read \App\Wiki $wiki
 * @method static \Database\Factories\WikiManagerFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiManager newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiManager newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiManager query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiManager whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiManager whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiManager whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiManager whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiManager whereWikiId($value)
 * @mixin \Eloquent
 */
class WikiManager extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'wiki_id',
        'user_id',
    ];

    // TODO remove these relationships if they are not used...
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @psalm-return \Illuminate\Database\Eloquent\Relations\BelongsTo<Wiki>
     */
    public function wiki(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Wiki::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @psalm-return \Illuminate\Database\Eloquent\Relations\BelongsTo<User>
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function email(): void
    {
        $this->user()->get(['email'])->first();
    }
}
