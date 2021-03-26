<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Wiki
 *
 * @property int $id
 * @property string $domain
 * @property string $sitename
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\WikiSetting[] $settings
 * @property-read int|null $settings_count
 * @property-read \App\WikiDb|null $wikiDb
 * @property-read \App\WikiDb|null $wikiDbVersion
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $wikiManagers
 * @property-read int|null $wiki_managers_count
 * @property-read \App\QueryserviceNamespace|null $wikiQueryserviceNamespace
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Wiki newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Wiki newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Wiki onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Wiki query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Wiki whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Wiki whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Wiki whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Wiki whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Wiki whereSitename($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Wiki whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Wiki withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Wiki withoutTrashed()
 * @mixin \Eloquent
 */
class Wiki extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sitename',
        'domain',
    ];

    protected $dates = [
        'deleted_at',
    ];

    public function wikiDbVersion()
    {
        return $this->hasOne(WikiDb::class)->select(['id', 'wiki_id', 'version']);
    }

    // TODO these should just be on the backend model? =] Or marked as a private relationship or something?
    // OR some sort of access control needs to be done..
    public function wikiDb()
    {
        return $this->hasOne(WikiDb::class);
    }

    public function wikiQueryserviceNamespace()
    {
        return $this->hasOne(QueryserviceNamespace::class);
    }

    // FIXME: rename to privateSettings / allSettings for clarity?
    public function settings()
    {
        return $this->hasMany(WikiSetting::class);
    }

    public function publicSettings() {
        return $this->settings()->whereIn('name',
        [
            // FIXME: this list is evil and should be kept in sync with WikiSettingController?!
            'wgDefaultSkin',
            'wgLogo',
            'wwExtEnableConfirmAccount',
            'wwWikibaseStringLengthString',
            'wwWikibaseStringLengthMonolingualText',
            'wwWikibaseStringLengthMultilang',
            'wikibaseFedPropsEnable',
            'wikibaseManifestEquivEntities',
        ]
    );
    }

    public function wikiManagers()
    {
        // TODO should this be hasMany ?
        return $this->belongsToMany(User::class, 'wiki_managers')->select(['email']);
    }
}
