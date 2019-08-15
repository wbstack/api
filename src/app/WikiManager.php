<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WikiManager extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'wiki_id',
        'user_id',
    ];

    // TODO remove these relationships if they are not used...
    public function wiki() {
        return $this->belongsTo( Wiki::class );
    }
    public function user() {
        return $this->belongsTo( User::class );
    }

}
