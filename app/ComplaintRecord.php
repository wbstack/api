<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\ComplaintRecord.
 *
 * @property int $id
 * @property string|null $name
 * @property string|null $mail_address
 * @property string $reason
 * @property string $offending_urls
 * @property \Illuminate\Support\Carbon|null $dispatched_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @mixin \Eloquent
 */
class ComplaintRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'mail_address',
        'reason',
        'offending_urls',
    ];

    public function markAsDispatched()
    {
        $this->dispatched_at = Carbon::now();
    }
}
