<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * App\WikiDomain.
 *
 * @property int $id
 * @property string $domain
 * @property int|null $wiki_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Wiki|null $wiki
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiDomain newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiDomain newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiDomain query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiDomain whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiDomain whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiDomain whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiDomain whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiDomain whereWikiId($value)
 *
 * @mixin \Eloquent
 */
class WikiDomain extends Model {
    protected $fillable = [
        'domain',
        'wiki_id',
    ];

    public function wiki(): HasOne {
        return $this->hasOne(Wiki::class);
    }
}
