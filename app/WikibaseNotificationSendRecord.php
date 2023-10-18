<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WikibaseNotificationSendRecord extends Model
{
    use HasFactory;

    const FIELDS = [
        'notification_type',
    ];

    protected $fillable = self::FIELDS;

    protected $visible = self::FIELDS;

    protected $casts = [
        'notification_type' => 'string',
    ];
}
