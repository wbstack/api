<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ScheduledSuspension extends Model
{
    protected $fillable = [
        'active_from',
        'wiki_id'
    ];
}
