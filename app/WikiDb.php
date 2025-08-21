<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\WikiDb.
 *
 * @property int $id
 * @property string $name
 * @property string $prefix
 * @property string $user
 * @property string $password
 * @property string $version
 * @property int|null $wiki_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Wiki|null $wiki
 *
 * @method static \Database\Factories\WikiDbFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiDb newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiDb newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiDb query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiDb whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiDb whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiDb whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiDb wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiDb wherePrefix($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiDb whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiDb whereUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiDb whereVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiDb whereWikiId($value)
 *
 * @mixin \Eloquent
 */
class WikiDb extends Model {
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'prefix',
        'user',
        'password',
        'version',
        'wiki_id',
    ];

    /**
     * @psalm-return \Illuminate\Database\Eloquent\Relations\BelongsTo<Wiki>
     */
    public function wiki(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
        return $this->belongsTo(Wiki::class);
    }
}
