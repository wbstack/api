<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\WikiManager.
 *
 * @property int $id
 * @property int $user_id
 * @property int $wiki_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Wiki $wiki
 *
 * @method static \Database\Factories\WikiManagerFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiManager newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiManager newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiManager query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiManager whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiManager whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiManager whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiManager whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiManager whereWikiId($value)
 *
 * @mixin \Eloquent
 */
class WikiManager extends Model {
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
    public function wiki(): BelongsTo {
        return $this->belongsTo(Wiki::class);
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function email(): void {
        $this->user()->get(['email'])->first();
    }
}
