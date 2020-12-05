<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\QsBatch
 *
 * @property int $id
 * @property int $eventFrom
 * @property int $eventTo
 * @property int $wiki_id
 * @property string $entityIds
 * @property int $done
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Wiki $wiki
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QsBatch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QsBatch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QsBatch query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QsBatch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QsBatch whereDone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QsBatch whereEntityIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QsBatch whereEventFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QsBatch whereEventTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QsBatch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QsBatch whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QsBatch whereWikiId($value)
 * @mixin \Eloquent
 */
class QsBatch extends Model
{
    protected $fillable = [
        'done',
        'eventFrom',
        'eventTo',
        'wiki_id',
        'entityIds',
    ];

    public function wiki()
    {
        return $this->belongsTo(Wiki::class);
    }
}
