<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Wiki extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sitename',
        'domain',
    ];

    public function wikiDbVersion() {
        return $this->hasOne( WikiDb::class )->select(array('id', 'wiki_id','version'));
    }

    public function wikiManagers() {
      return $this->belongsToMany(User::class, 'wiki_managers');
    }

}
