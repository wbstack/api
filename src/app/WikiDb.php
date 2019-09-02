<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WikiDb extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'prefix',
        'user',
        'password',
        'version',
        'wiki_id',
    ];

    public function wiki()
    {
        return $this->belongsTo(Wiki::class);
    }
}
