<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
 * @method static \Database\Factories\WikiSettingFactory factory(...$parameters)
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
    use HasFactory;

    public const wwExtEnableElasticSearch = 'wwExtEnableElasticSearch';
    public const wwExtEnableWikibaseLexeme = 'wwExtEnableWikibaseLexeme';
    public const wgSecretKey = 'wgSecretKey';
    public const wgLogo = 'wgLogo';
    public const wgFavicon = 'wgFavicon';
    public const wgOAuth2PrivateKey = 'wgOAuth2PrivateKey';
    public const wgOAuth2PublicKey = 'wgOAuth2PublicKey';

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
