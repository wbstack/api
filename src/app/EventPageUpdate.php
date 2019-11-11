<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EventPageUpdate extends Model
{
    protected $fillable = [
        'wiki_id',
        'title',
        'namespace',
    ];

    public function wiki()
    {
        return $this->belongsTo(Wiki::class);
    }
}
