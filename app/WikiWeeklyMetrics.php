<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WikiWeeklyMetrics extends Model
{
    use HasFactory;

    protected $table = 'wiki_weekly_metrics';

    protected $primaryKey = 'id';

    public $incrementing = false; // Disable auto-increment

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'wiki_id',
        'date',
        'pages',
        'is_deleted',
    ];
}
