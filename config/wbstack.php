<?php

return [

    'subdomain_suffix' => env('WBSTACK_SUBDOMAIN_SUFFIX', '.wiki.opencura.com'),

    'ui_url' => env('WBSTACK_UI_URL', 'https://wbstack.com'),

    'wiki_db_provision_version' => env('WBSTACK_WIKI_DB_PROVISION_VERSION', 'mw1.37-fp-wbs1'),
    'wiki_db_use_version' => env('WBSTACK_WIKI_DB_USE_VERSION', 'mw1.37-fp-wbs1'),
    'wiki_hard_delete_threshold' => env('WBSTACK_HARD_DELETE_THRESHOLD', 30),

];