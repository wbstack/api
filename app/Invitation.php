<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Invitation.
 *
 * @property int $id
 * @property string $code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Database\Factories\InvitationFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Invitation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Invitation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Invitation query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Invitation whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Invitation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Invitation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Invitation whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Invitation extends Model {
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
