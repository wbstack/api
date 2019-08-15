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

    public function wikiDb() {
        return $this->hasOne( WikiDb::class );
    }

    public function wikiManagers() {
      return $this->belongsToMany(User::class, 'wiki_managers');
    }

}
