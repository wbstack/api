<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WikiDailyMetrics extends Model {
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
        'daily_actions',
        'weekly_actions',
        'monthly_actions',
        'quarterly_actions',
        'number_of_triples',
        'monthly_casual_users',
        'monthly_active_users',

    ];

    // list of properties which are actual wiki metrics
    public static $metricNames = [
        'pages',
        'is_deleted',
        'daily_actions',
        'weekly_actions',
        'monthly_actions',
        'quarterly_actions',
        'number_of_triples',
        'monthly_casual_users',
        'monthly_active_users',
    ];

    public function areMetricsEqual(WikiDailyMetrics $wikiDailyMetrics): bool {
        foreach (self::$metricNames as $field) {
            if ($this->$field != $wikiDailyMetrics->$field) {
                return false;
            }
        }

        return true;
    }
}
