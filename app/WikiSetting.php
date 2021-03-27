<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\WikiSetting.
 *
 * @property int $id
 * @property string $name
 * @property string $value
 * @property int|null $wiki_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Wiki|null $wiki
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiSetting whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiSetting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiSetting whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\WikiSetting whereWikiId($value)
 * @mixin \Eloquent
 */
class WikiSetting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'wiki_id',
        'name',
        'value',
    ];

    public function wiki()
    {
        return $this->belongsTo(Wiki::class);
    }
}
