<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\QsBatch.
 *
 * @property int $id
 * @property int $wiki_id
 * @property string $entityIds
 * @property \DateTime $pending_since
 * @property int $done
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Wiki $wiki
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QsBatch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QsBatch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QsBatch query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QsBatch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QsBatch whereDone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QsBatch whereEntityIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QsBatch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QsBatch whereIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QsBatch whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QsBatch whereWikiId($value)
 *
 * @mixin \Eloquent
 */
class QsBatch extends Model {
    use HasFactory;

    protected $fillable = [
        'done',
        'wiki_id',
        'entityIds',
        'pending_since',
        'failed',
        'processing_attempts',
    ];

    public function wiki(): BelongsTo {
        return $this->belongsTo(Wiki::class);
    }

    #[\Override]
    protected function casts(): array {
        return [
            'pending_since' => 'datetime',
        ];
    }
}
