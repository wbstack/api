<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmptyWikibaseNotification extends Model
{
    use HasFactory;

    const FIELDS = [
        'domain',
        'first_edited',
        'empty_wiki_notification'
    ];

    protected $fillable = self::FIELDS;

    protected $visible = self::FIELDS;

    protected $casts = [
        'domain' => 'string',
        'last_edited' => 'datetime',
        'empty_wiki_notification' => 'boolean'
    ];
}
