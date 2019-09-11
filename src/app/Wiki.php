<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wiki extends Model
{

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sitename',
        'domain',
    ];

    protected $dates = [
        'deleted_at',
    ];

    public function wikiDbVersion()
    {
        return $this->hasOne(WikiDb::class)->select(['id', 'wiki_id', 'version']);
    }

    // TODO this should just be on the backend model? =] Or marked as a private relationship or something?
    // OR some sort of access controll needs to be done..
    public function wikiDb()
    {
        return $this->hasOne(WikiDb::class);
    }

    public function wikiManagers()
    {
        return $this->belongsToMany(User::class, 'wiki_managers')->select(['email']);
    }
}
