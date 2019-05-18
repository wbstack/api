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
        'subdomain',
        'sitename',
        'metanamespace',
    ];

    public function wikiDb() {
        return $this->hasOne( WikiDb::class );
    }

//    /**
//     * The attributes excluded from the model's JSON form.
//     *
//     * @var array
//     */
//    protected $hidden = [
//        'password',
//    ];

}
