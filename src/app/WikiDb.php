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

    // TODO starting to see the issue of internal vs extenal apis using the same lumen app here...
// should this be private? should it not be?
//    /**
//     * The attributes excluded from the model's JSON form.
//     *
//     * @var array
//     */
//    protected $hidden = [
//        'password',
//    ];
}
