<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * App\Invitation.
 *
 * @property int $id
 * @property string $code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Invitation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Invitation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Invitation query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Invitation whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Invitation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Invitation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Invitation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Invitation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
    ];
}
