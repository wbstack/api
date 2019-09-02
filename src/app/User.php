<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * TODO SHIFT NOTE, switch from verified field to email_verified_at..
 * SHIFT NOTE, used to use Authorizable
 * SHIFT NOTE, used to implement AuthorizableContract
 */
class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email', 'password', 'verified'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        // SHIFT, remember_token was not added in the migration
        //'remember_token',
    ];

    // TODO this should be done with roles or something in the DB....
    public function isAdmin() {
      return $this->email == 'adamshorland@gmail.com';
    }
    public function managesWikis() {
      return $this->belongsToMany(Wiki::class, 'wiki_managers');
    }

// SHIFT this was not added in the migration
    // /**
    //  * The attributes that should be cast to native types.
    //  *
    //  * @var array
    //  */
    // protected $casts = [
    //     'email_verified_at' => 'datetime',
    // ];
}
