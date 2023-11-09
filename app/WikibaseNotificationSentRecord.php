<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WikibaseNotificationSentRecord extends Model
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

    public function wiki(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Wiki::class);
    }
}
