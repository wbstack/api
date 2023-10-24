<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\EventPageUpdate.
 *
 * @property int $id
 * @property int $wiki_id
 * @property string $title
 * @property int $namespace
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Wiki $wiki
 * @method static \Illuminate\Database\Eloquent\Builder|\App\EventPageUpdate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\EventPageUpdate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\EventPageUpdate query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\EventPageUpdate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\EventPageUpdate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\EventPageUpdate whereNamespace($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\EventPageUpdate whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\EventPageUpdate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\EventPageUpdate whereWikiId($value)
 * @mixin \Eloquent
 */
class EventPageUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'wiki_id',
        'title',
        'namespace',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @psalm-return \Illuminate\Database\Eloquent\Relations\BelongsTo<Wiki>
     */
    public function wiki(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Wiki::class);
    }
}
