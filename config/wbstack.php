<?php

return [
    'subdomain_suffix' => env('WBSTACK_SUBDOMAIN_SUFFIX', '.wiki.opencura.com'),

    'ui_url' => env('WBSTACK_UI_URL', 'https://wbstack.com'),

    'wiki_db_provision_version' => env('WBSTACK_WIKI_DB_PROVISION_VERSION', 'mw1.39-wbs1'),
    'wiki_db_use_version' => env('WBSTACK_WIKI_DB_USE_VERSION', 'mw1.39-wbs1'),
    'wiki_hard_delete_threshold' => env('WBSTACK_HARD_DELETE_THRESHOLD', 30),
    'wiki_max_per_user' => env('WBSTACK_MAX_PER_USER', false),
    'wiki_empty_notification_threshold' => env('WBSTACK_EMPTY_NOTIFICATION_THRESHOLD', 30),

    'platform_summary_inactive_threshold' => env('WBSTACK_SUMMARY_INACTIVE_THRESHOLD', 60 * 60 * 24 * 90),
    'platform_summary_creation_rate_ranges' => array_filter(
        explode(',', env('WBSTACK_SUMMARY_CREATION_RATE_RANGES', ''))
    ),

    'elasticsearch_hosts' => array_filter(explode(',', env('ELASTICSEARCH_HOST', ''))),
    'elasticsearch_enabled_by_default' => env('WBSTACK_ELASTICSEARCH_ENABLED_BY_DEFAULT', false),

    'signup_throttling_limit' => env('WBSTACK_SIGNUP_THROTTLING_LIMIT', ''),
    'signup_throttling_range' => env('WBSTACK_SIGNUP_THROTTLING_RANGE', ''),

    'qs_batch_pending_timeout' => env('WBSTACK_QS_BATCH_PENDING_TIMEOUT', 'PT300S'),
    'qs_batch_mark_failed_after' => intval(env('WBSTACK_QS_BATCH_MARK_FAILED_AFTER', '3')),
];
