<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserVerificationToken extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'token',
    ];

    public function user() {
        return $this->belongsTo( User::class );
    }

}
