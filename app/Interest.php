<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Interest
 *
 * @property int $id
 * @property string $email
 * @property int $invitaitonSent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Interest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Interest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Interest query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Interest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Interest whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Interest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Interest whereInvitaitonSent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Interest whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Interest extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email',
        'invitaitonSent',
    ];
}
