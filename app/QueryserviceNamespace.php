<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\QueryserviceNamespace.
 *
 * @property int $id
 * @property string $namespace
 * @property string $backend
 * @property int|null $wiki_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Wiki|null $wiki
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QueryserviceNamespace newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QueryserviceNamespace newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QueryserviceNamespace query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QueryserviceNamespace whereBackend($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QueryserviceNamespace whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QueryserviceNamespace whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QueryserviceNamespace whereNamespace($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QueryserviceNamespace whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\QueryserviceNamespace whereWikiId($value)
 *
 * @mixin \Eloquent
 */
class QueryserviceNamespace extends Model {
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'namespace',
        // 'internalHost',
        'backend',
    ];

    public function wiki(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
        return $this->belongsTo(Wiki::class);
    }
}
