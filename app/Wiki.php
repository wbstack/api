<?php

namespace App;

use App\Helper\DomainHelper;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Wiki.
 *
 * @property int $id
 * @property string $domain
 * @property string $sitename
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $description
 * @property int $is_featured
 * @property string|null $wiki_deletion_reason
 * @property-read mixed $domain_decoded
 * @property-read Collection<int, ReviewSubmission> $reviewSubmissions
 * @property-read int|null $review_submissions_count
 * @property-read Collection<int, WikiSetting> $settings
 * @property-read int|null $settings_count
 * @property-read WikiDb|null $wikiDb
 * @property-read WikiDb|null $wikiDbVersion
 * @property-read Collection<int, WikiEntityImport> $wikiEntityImports
 * @property-read int|null $wiki_entity_imports_count
 * @property-read WikiProfile|null $wikiLatestProfile
 * @property-read WikiLifecycleEvents|null $wikiLifecycleEvents
 * @property-read Collection<int, WikiManager> $wikiManagers
 * @property-read int|null $wiki_managers_count
 * @property-read Collection<int, User> $wikiManagersWithEmail
 * @property-read int|null $wiki_managers_with_email_count
 * @property-read Collection<int, WikiNotificationSentRecord> $wikiNotificationSentRecords
 * @property-read int|null $wiki_notification_sent_records_count
 * @property-read QueryserviceNamespace|null $wikiQueryserviceNamespace
 * @property-read WikiSiteStats|null $wikiSiteStats
 *
 * @method static \Database\Factories\WikiFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wiki newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wiki newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wiki onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wiki query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wiki whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wiki whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wiki whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wiki whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wiki whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wiki whereIsFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wiki whereSitename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wiki whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wiki whereWikiDeletionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wiki withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wiki withoutTrashed()
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

    public function reviewSubmissions(): HasMany {
        return $this->hasMany(ReviewSubmission::class);
    }

    public function wikiDbVersion() {
        return $this->hasOne(WikiDb::class)->select(['id', 'wiki_id', 'version']);
    }

    // TODO these should just be on the backend model? =] Or marked as a private relationship or something?
    // OR some sort of access control needs to be done..
    public function wikiDb(): HasOne {
        return $this->hasOne(WikiDb::class);
    }

    public function wikiSiteStats(): HasOne {
        return $this->hasOne(WikiSiteStats::class);
    }

    public function wikiLifecycleEvents(): HasOne {
        return $this->hasOne(WikiLifecycleEvents::class);
    }

    public function wikiNotificationSentRecords(): HasMany {
        return $this->hasMany(WikiNotificationSentRecord::class);
    }

    public function wikiEntityImports(): HasMany {
        return $this->hasMany(WikiEntityImport::class);
    }

    public function wikiManagers(): HasMany {
        return $this->hasMany(WikiManager::class);
    }

    public function wikiQueryserviceNamespace(): HasOne {
        return $this->hasOne(QueryserviceNamespace::class);
    }

    // FIXME: rename to privateSettings / allSettings for clarity?
    public function settings(): HasMany {
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
    protected function domainDecoded(): Attribute {
        return Attribute::make(get: function () {
            return DomainHelper::decode($this->domain);
        });
    }

    public function wikiLatestProfile() {
        return $this->hasOne(WikiProfile::class)->latestOfMany();
    }

    public function setSetting(string $name, string $value): void {
        $this->settings()->updateOrCreate(
            ['name' => $name],
            ['value' => $value]
        );
    }

    public function deleteSetting(string $name): ?string {
        return $this->settings()->where('name', $name)->delete();
    }

    protected function casts(): array {
        return [
            'deleted_at' => 'datetime',
        ];
    }
}
