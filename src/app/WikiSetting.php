<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WikiSetting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'wiki_id',
        'name',
        'value',
    ];

    public function wiki()
    {
        return $this->belongsTo(Wiki::class);
    }
}
