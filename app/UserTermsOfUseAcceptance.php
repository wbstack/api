<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTermsOfUseAcceptance extends Model {
    use HasFactory;

    const FIELDS = [
        'user_id',
        'tou_version',
        'tou_accepted_at',
    ];

    protected $fillable = self::FIELDS;

    protected $visible = self::FIELDS;

    protected $casts = [
        'tou_version' => TermsOfUseVersion::class,
        'tou_accepted_at' => 'datetime',
    ];

    protected $table = 'tou_acceptances';
}
