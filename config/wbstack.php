<?php

return [

    'subdomain_suffix' => env('WBSTACK_SUBDOMAIN_SUFFIX', '.wiki.opencura.com'),

    'ui_url' => env('WBSTACK_UI_URL', 'https://wbstack.com'),

    'wiki_db_provision_version' => env('WBSTACK_WIKI_DB_PROVISION_VERSION', 'mw1.39-wbs1'),
    'wiki_db_use_version' => env('WBSTACK_WIKI_DB_USE_VERSION', 'mw1.39-wbs1'),
    'wiki_hard_delete_threshold' => env('WBSTACK_HARD_DELETE_THRESHOLD', 30),
    'wiki_max_per_user' => env('WBSTACK_MAX_PER_USER', false),

    'platform_summary_inactive_threshold' => env('WBSTACK_SUMMARY_INACTIVE_THRESHOLD', 60 * 60 * 24 * 90),
    'platform_summary_signup_ranges' => explode(',', env('WBSTACK_SUMMARY_SIGNUP_RANGES', 'PT24H,P30D')),

    'elasticsearch_host' => env('ELASTICSEARCH_HOST', false),
    'elasticsearch_enabled_by_default' => env('WBSTACK_ELASTICSEARCH_ENABLED_BY_DEFAULT', false),

];