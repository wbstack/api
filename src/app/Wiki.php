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

    // TODO this should just be on the backend model? =]
    // OR some sort of access controll needs to be done..
    public function wikiDb()
    {
        return $this->hasOne(WikiDb::class);
    }

    public function wikiManagers()
    {
        // TODO FIXME FOR RELEASE
        // Really this shouldnt use the User model? or at least not with the default set of private fileds?
        // When this is retrieved by the user owning the model we want 1 set of fields returned.
        // If it is returned by an admin, perhaps another
        // If it is returned by the owner of a wiki for which the user is a manager, then another....
        return $this->belongsToMany(User::class, 'wiki_managers');
    }
}
