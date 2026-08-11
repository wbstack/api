<?php

namespace App;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ScheduledSuspension extends Model
{
    protected $fillable = [
        'active_from',
        'wiki_id'
    ];

    public function Wiki(): BelongsTo {
        return $this->belongsTo(Wiki::class);
    }

    public function schedulingDue(): bool {
        return CarbonImmutable::now() > CarbonImmutable::parse($this->active_from);
    }
}
