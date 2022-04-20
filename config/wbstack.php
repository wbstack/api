<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Feature toggles
    |--------------------------------------------------------------------------
    |
    | Collection of toggles for specific features within the platform.
    |
    */
    'disable_account_creation' => env('WBSTACK_DISABLE_ACCOUNT_CREATION', false),
    'disable_wiki_creation' => env('WBSTACK_DISABLE_WIKI_CREATION', false),

];