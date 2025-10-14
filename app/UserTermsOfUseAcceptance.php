<?php

namespace App;

use App\TermsOfUseVersion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTermsOfUseAcceptance extends Model {
    use HasFactory;

    public const FIELDS = [
        'user_id',
        'tou_version',
        'tou_accepted_at',
    ];

    protected $fillable = self::FIELDS;
    protected $visible = self::FIELDS;

    protected $casts = [
        'tou_version' => 'string',
        'tou_accepted_at' => 'datetime',
    ];

    protected $table = 'tou_acceptances';

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    // /**
    //  * Defines the relationship to TermsOfUseVersion using a non-standard key mapping.
    //  * Foreign key: 'tou_version' on this model.
    //  * Owner key: 'version' on TermsOfUseVersion model.
    //  */
    // public function termsOfUseVersion(): BelongsTo {
    //     return $this->belongsTo(TermsOfUseVersion::class, 'tou_version', 'version');
    // }
}
