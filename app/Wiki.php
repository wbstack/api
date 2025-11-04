<?php

namespace App;

use App\Helper\DomainHelper;
use App\Services\MediaWikiHostResolver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Wiki.
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
 *
 * @method static \Database\Factories\WikiFactory factory(...$parameters)
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
 *
 * @mixin \Eloquent
 */
class Wiki extends Model {
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sitename',
        'domain',
        'description',
        'is_featured',
        'wiki_deletion_reason',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'domain_decoded',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function wikiDbVersion() {
        return $this->hasOne(WikiDb::class)->select(['id', 'wiki_id', 'version']);
    }

    // TODO these should just be on the backend model? =] Or marked as a private relationship or something?
    // OR some sort of access control needs to be done..
    public function wikiDb(): \Illuminate\Database\Eloquent\Relations\HasOne {
        return $this->hasOne(WikiDb::class);
    }

    public function wikiSiteStats(): \Illuminate\Database\Eloquent\Relations\HasOne {
        return $this->hasOne(WikiSiteStats::class);
    }

    public function wikiLifecycleEvents(): \Illuminate\Database\Eloquent\Relations\HasOne {
        return $this->hasOne(WikiLifecycleEvents::class);
    }

    public function wikiNotificationSentRecords(): \Illuminate\Database\Eloquent\Relations\HasMany {
        return $this->hasMany(WikiNotificationSentRecord::class);
    }

    public function wikiEntityImports(): \Illuminate\Database\Eloquent\Relations\HasMany {
        return $this->hasMany(WikiEntityImport::class);
    }

    public function wikiManagers(): \Illuminate\Database\Eloquent\Relations\HasMany {
        return $this->hasMany(WikiManager::class);
    }

    public function wikiQueryserviceNamespace(): \Illuminate\Database\Eloquent\Relations\HasOne {
        return $this->hasOne(QueryserviceNamespace::class);
    }

    // FIXME: rename to privateSettings / allSettings for clarity?
    public function settings(): \Illuminate\Database\Eloquent\Relations\HasMany {
        return $this->hasMany(WikiSetting::class);
    }

    public function publicSettings() {
        return $this->settings()->whereIn('name',
            [
                'wgLogo',
                'wgReadOnly',
                // FIXME: this list is evil and should be kept in sync with WikiSettingController?!
                'wgDefaultSkin',
                'wwExtEnableConfirmAccount',
                'wwExtEnableWikibaseLexeme',
                'wwWikibaseStringLengthString',
                'wwWikibaseStringLengthMonolingualText',
                'wwWikibaseStringLengthMultilang',
                'wikibaseFedPropsEnable',
                'wikibaseManifestEquivEntities',
                'wwUseQuestyCaptcha',
                'wwCaptchaQuestions',
            ]
        );
    }

    public function wikiManagersWithEmail() {
        // TODO should this be hasMany ?
        return $this->belongsToMany(User::class, 'wiki_managers')->select(['email']);
    }

    /**
     * Get logo directory path
     */
    public static function getLogosDirectory(int $wiki_id): string {
        return self::getSiteDirectory($wiki_id) . '/logos';
    }

    /**
     * Get site directory path
     */
    public static function getSiteDirectory(int $wiki_id): string {
        $siteDir = md5($wiki_id . md5($wiki_id));

        return 'sites/' . $siteDir;
    }

    /**
     * Convert the IDN formatted domain name to it's Unicode representation.
     */
    public function getDomainDecodedAttribute(): string {
        return DomainHelper::decode($this->domain);
    }

    public function wikiLatestProfile() {
        return $this->hasOne(WikiProfile::class)->latestOfMany();
    }

    /**
     * Retrieve correct backend host for the mediawiki version of this wiki 
     */
    public function getBackendHost() {
        return (new MediaWikiHostResolver)->getBackendHostForDomain($this->domain);
    }
}
