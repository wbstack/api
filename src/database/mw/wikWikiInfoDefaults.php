<?php

// TODO include this and the other modified files in the docker image...

const WIKIINFO_KEY = 'WikWikiWikInfo';

// Check the site being accessed exists..
call_user_func(function () {
    $response = file_get_contents(__DIR__.'/wikWikiInfoDefaults.json');
    $wikiInfo = json_decode($response);

    // Set the model to the globals to be used by local settings..
    /* @var WikiInfo[] $wikiInfo */
    $GLOBALS[WIKWIKI_GLOBAL] = $wikiInfo;
    // END generic getting of wiki info from domain

    if ($wikiInfo === [] || $wikiInfo === null) {
        http_response_code(404);
        echo 'wikiInfo not set...';
        die();
    }
});
