<?php

// TODO include this and the other modified files in the docker image...
// TODO should this use something like setGlobalForGeneralMaintScript instead?

const WIKIINFO_KEY = 'WikWikiWikInfo';

// Check the site being accessed exists..
call_user_func(function () {
    $response = file_get_contents(__DIR__.'/wikWikiInfoDefaults.json');

    // Set the model to the globals to be used by local settings..
    $GLOBALS[WIKWIKI_GLOBAL] = WikWiki::newFromApiResult(json_decode($response));
    // END generic getting of wiki info from domain

});
