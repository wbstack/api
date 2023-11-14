<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WikiNotificationSentRecord extends Model
{
    use HasFactory;

    const FIELDS = [
        'notification_type',
        'user_id'
    ];

    protected $fillable = self::FIELDS;

    protected $visible = self::FIELDS;

    protected $casts = [
        'notification_type' => 'string',
    ];
}
