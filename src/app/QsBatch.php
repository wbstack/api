<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class QsBatch extends Model
{
    protected $fillable = [
        'done',
        'eventFrom',
        'eventTo',
        'wiki_id',
        'entityIds',
    ];

    public function wiki()
    {
        return $this->belongsTo(Wiki::class);
    }
}
