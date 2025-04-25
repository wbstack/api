<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WikiDailyMetrics extends Model
{
    use HasFactory;

    protected $table = 'wiki_daily_metrics';

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

    // list of properties which are actual wiki metrics
    static public $metricNames = [
        'pages',
        'is_deleted',
    ];

    public function areMetricsEqual(WikiDailyMetrics $wikiDailyMetrics) {

        foreach(self::$metricNames as $field) {
            if ($this->$field !== $wikiDailyMetrics->$field) {
                return false;
            }
        }
        return true;
    }
}
