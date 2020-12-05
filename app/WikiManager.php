<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\WikiManager
 *
 * @property int $id
 * @property int $user_id
 * @property int $wiki_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\User $user
 * @property-read \App\Wiki $wiki
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
    public function wiki()
    {
        return $this->belongsTo(Wiki::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function email()
    {
        $this->user()->email;
    }
}
