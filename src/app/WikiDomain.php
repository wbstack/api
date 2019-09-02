<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WikiDomain extends Model
{

    protected $fillable = [
        'domain',
        'wiki_id',
    ];

    public function wiki()
    {
        return $this->hasOne(Wiki::class);
    }

}
