<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTermsOfUseAcceptance extends Model {
    use HasFactory;

    public $incrementing = false;
    protected $primaryKey = null;
    protected $keyType = 'string';

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

    protected function setKeysForSaveQuery($query) {
        return $query
            ->where('user_id', $this->getAttribute('user_id'))
            ->where('tou_version', $this->getAttribute('tou_version'));
    }
}
