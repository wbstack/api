<?php

namespace App;

use Laravel\Cashier\Billable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * TODO SHIFT NOTE, switch from verified field to email_verified_at..
 * SHIFT NOTE, used to use Authorizable
 * SHIFT NOTE, used to implement AuthorizableContract.
 */
class User extends Authenticatable
{
    use HasApiTokens, Notifiable, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email', 'password', 'verified',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        // cashier
        'stripe_id',
        'trial_ends_at',
        'card_brand',
        'card_last_four',
    ];

    // TODO this should be done with roles or something in the DB....
    public function isAdmin()
    {
        return $this->email == 'adamshorland@gmail.com';
    }

    public function managesWikis()
    {
        return $this->belongsToMany(Wiki::class, 'wiki_managers');
    }
}
