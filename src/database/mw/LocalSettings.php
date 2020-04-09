<?php

if ( !array_key_exists( WIKWIKI_GLOBAL, $GLOBALS ) ) {
    // We shouldn't reach here as all entry points should create this GLOBAL..
    // However if new things get introduced we will end up here.
    die('LS not got wiki info.');
}

//// START LOGGING
// Log to STDERR
if ( !defined( 'STDERR' ) ) {
    define( 'STDERR', fopen( 'php://stderr', 'w' ) );
}
require_once __DIR__ . '/WikWikiSpi.php';
require_once __DIR__ . '/WikWikiLogger.php';
if ( !isset( $maintClass ) || ( isset( $maintClass ) && $maintClass !== 'PHPUnitMaintClass' ) ) {
    $wgMWLoggerDefaultSpi = [
        'class' => \WikWikiSpi::class,
        'args' => [[
            'ignoreLevels' => [
                'debug',
                'info',
            ],
            'ignoreAllInGroup' => [
                'DBPerformance',
                'objectcache',// ideally want to show objectcache errors, but not warnings
            ],
            'logAllInGroup' => [
                'WBSTACK',
                'HttpError',
                'SpamBlacklistHit',
                'security',
                'exception-json',
                //'error',
                'fatal',
                'badpass',
                'badpass-priv',
                'api-warning',
            ],
            'logAllInGroupExceptDebug' => [
                //'Wikibase',
            ],
        ]],
    ];
}
//// END LOGGING

/** @var WikWiki $wikWiki */
$wikWiki = $GLOBALS[WIKWIKI_GLOBAL];

if( substr($wikWiki->requestDomain,-9, 9) === 'localhost' ) {
    // TODO this code path shouldn't be accessible when in PROD
    // TODO fix totally hardcoded port for dev us
    $wgServer = "//" . $wikWiki->requestDomain . ":8083";
    // Internal is on 8073...
    if(getenv('WBSTACK_LOAD_MW_INTERNAL') === 'yes' && file_exists( __DIR__ . '/InternalSettings.php' )){
        $wgServer = "//" . $wikWiki->requestDomain . ":8073";
    }
    $wgShowExceptionDetails = true;
    ini_set( 'display_errors', 1 );
} else {
    $wgServer = "//" . $wikWiki->domain;
    ini_set( 'display_errors', 0 );

}

$wgDBname = $wikWiki->wiki_db->name;

$wgDBservers = [
    [
        'host' => getenv('MW_DB_SERVER_MASTER'),
        'dbname' => $wgDBname,
        'user' => $wikWiki->wiki_db->user,
        'password' => $wikWiki->wiki_db->password,
        'type' => "mysql",
        'flags' => DBO_DEFAULT,
        'load' => 0,
    ],
    [
        'host' => getenv('MW_DB_SERVER_REPLICA'),
        'dbname' => $wgDBname,
        'user' => $wikWiki->wiki_db->user,
        'password' => $wikWiki->wiki_db->password,
        'type' => "mysql",
        'flags' => DBO_DEFAULT,
        'max lag' => 10,
        'load' => 1,
    ],
];

$wgDBprefix = $wikWiki->wiki_db->prefix . '_';
$wgDBTableOptions = "ENGINE=InnoDB, DEFAULT CHARSET=binary";

## Keys
$wgAuthenticationTokenVersion = "1";
$wgSecretKey = $wikWiki->getSetting('wgSecretKey');

// TODO no idea if this is right?
$wgScriptPath = "/w";
$wgArticlePath = "/wiki/$1";

$wgSitename = $wikWiki->sitename;

// So we are uniform, have the project namespace as Project
$wgMetaNamespace = 'Project';

// TODO custom favicons
$wgFavicon = "{$wgScriptPath}/favicon.ico";

// TODO sort out directories and stuff...?
//$wgUploadDirectory = "{$IP}/images/docker/{$dockerDb}";
//$wgUploadPath = "{$wgScriptPath}/images/docker/{$dockerDb}";
//$wgTmpDirectory = "{$wgUploadDirectory}/tmp";

// TODO email (use a service?)
$wgEnableEmail = true;
$wgEnableUserEmail = false;
$wgAllowHTMLEmail = true;
// enable email authentication (confirmation) for this wiki
$wgEmailAuthentication = true;
// require email authentication
$wgEmailConfirmToEdit = true;
// TODO make this a real wbstack email address?
$wgEmergencyContact = "emergency.wbstack@addshore.com";
$wgPasswordSender = 'noreply@' . getenv('MW_EMAIL_DOMAIN');
$wgNoReplyAddress = 'noreply@' . getenv('MW_EMAIL_DOMAIN');

## Jobs
# For now jobs will run in the requests, this obviously isn't the ideal solution and really
# there should be a job running service deployed...
# This was set to 2 as Andra experienced a backup of jobs. https://github.com/addshore/wbstack/issues/51
$wgJobRunRate = 2;

## Notifications
$wgEnotifUserTalk = false;
$wgEnotifWatchlist = false;

## Files
$wgEnableUploads = false;
$wgAllowCopyUploads = false;
$wgUseInstantCommons = false;
$wgFileExtensions = array_merge( $wgFileExtensions,
    array( 'doc', 'xls', 'mpp', 'pdf', 'ppt', 'xlsx', 'jpg',
        'tiff', 'odt', 'odg', 'ods', 'odp', 'svg'
    )
);
//$wgFileExtensions[] = 'djvu';

## Locale
// TODO configuration
$wgShellLocale = "en_US.utf8";
$wgLanguageCode = "en";

## --- CACHING ---
$wgCacheDirectory = '/tmp/mw-cache';

//  Set this to true to disable cache updates on web requests.
$wgLocalisationCacheConf['manualRecache'] = true;

// Don't specific a redis cache when running dbless maint script
if($wikWiki->requestDomain !== 'maint') {
    /** @see RedisBagOStuff for a full explanation of these options. **/
    $wgMainCacheType = 'redis';
    $wgSessionCacheType = 'redis';
    $wgObjectCaches['redis'] = [
        'class'                => 'RedisBagOStuff',
        'servers'              => [ getenv('MW_REDIS_SERVER') . ':6379' ],
        // 'connectTimeout'    => 1,
        // 'persistent'        => false,
        // 'automaticFailOver' => true,
    ];
    if(getenv('MW_REDIS_PASSWORD') !== '') {
        // Only set the password if not empty
        $wgObjectCaches['redis']['password'] = getenv('MW_REDIS_PASSWORD');
    }
}

## --- PERMISSIONS ---
#Disallow anon editing for now
$wgGroupPermissions['*']['edit'] = false;
$wgGroupPermissions['*']['createpage'] = false;
#editinterface control
unset( $wgGroupPermissions['interface-admin'] );
unset( $wgRevokePermissions['interface-admin'] );
unset( $wgAddGroups['interface-admin'] );
unset( $wgRemoveGroups['interface-admin'] );
unset( $wgGroupsAddToSelf['interface-admin'] );
unset( $wgGroupsRemoveFromSelf['interface-admin'] );
$wgGroupPermissions['sysop']['editinterface'] = false;
#user JS CSS controls
$wgGroupPermissions['user']['editmyusercss'] = false;
$wgGroupPermissions['user']['editmyuserjs'] = false;
#allow emailconfirmed to skip captcha
$wgGroupPermissions['emailconfirmed']['skipcaptcha'] = true;

## --- SKINS ---
## TODO allow configuration of the default skin?
## TODO allow turning on and off skins
$wgDefaultSkin = 'vector';
wfLoadSkin( 'Vector' );
wfLoadSkin( 'Timeless' );
wfLoadSkin( 'Modern' );

## --- EXTENSIONS ---

#SyntaxHighlight_GeSHi
wfLoadExtension( 'SyntaxHighlight_GeSHi' );

# RevisionSlider
wfLoadExtension( 'RevisionSlider' );

# Mailgun
wfLoadExtension( 'Mailgun' );
$wgMailgunAPIKey = getenv('MW_MAILGUN_API_KEY');
$wgMailgunDomain = getenv('MW_MAILGUN_DOMAIN');

# TorBlock
wfLoadExtension( 'TorBlock' );

# Nuke
wfLoadExtension( 'Nuke' );

# ConfirmEdit
wfLoadExtensions([ 'ConfirmEdit', 'ConfirmEdit/ReCaptchaNoCaptcha' ]);
$wgCaptchaClass = 'ReCaptchaNoCaptcha';
$wgReCaptchaSendRemoteIP = true;
$wgReCaptchaSiteKey = getenv('MW_RECAPTCHA_SITEKEY');
$wgReCaptchaSecretKey = getenv('MW_RECAPTCHA_SECRETKEY');

# Wikibase
require_once "$IP/extensions/Wikibase/lib/WikibaseLib.php";
require_once "$IP/extensions/Wikibase/repo/Wikibase.php";
require_once "$IP/extensions/Wikibase/repo/ExampleSettings.php";
require_once "$IP/extensions/Wikibase/client/WikibaseClient.php";
require_once "$IP/extensions/Wikibase/client/ExampleSettings.php";

$wwWbSiteBaseUri = preg_replace( '!^//!', 'http://', $GLOBALS['wgServer'] );
$wwWbConceptUri = $wwWbSiteBaseUri . '/entity/';

$wgWBClientSettings['siteGlobalID'] = $wgDBname;
$wgWBClientSettings['repoScriptPath'] = '/w';
$wgWBClientSettings['repoArticlePath'] = '/wiki/$1';
$wgWBClientSettings['siteGroup'] = null;
$wgWBClientSettings['thisWikiIsTheRepo'] = true;
$wgWBClientSettings['repoUrl'] = $GLOBALS['wgServer'];
$wgWBClientSettings['repoSiteName'] = $GLOBALS['wgSitename'];
$wgWBClientSettings['repositories'] = [
    '' => [
        // Use false (meaning the local wiki's database) if this wiki is the repo,
        // otherwise default to null (meaning we can't access the repo's DB directly).
        'repoDatabase' => false,
        'baseUri' => $wwWbConceptUri,
        'entityNamespaces' => [
            'item' => 120,
            'property' => 122,
        ],
        'prefixMapping' => [ '' => '' ],
    ]
];

// TODO below setting will be empty by default in the future and we could remove them
$wgWBRepoSettings['siteLinkGroups'] = [];
// TODO below setting will be empty by default in the future and we could remove them
$wgWBRepoSettings['specialSiteLinkGroups'] = [];
$wgWBRepoSettings['dataRightsUrl'] = null;
$wgWBRepoSettings['dataRightsText'] = 'None yet set.';
$wgWBRepoSettings['conceptBaseUri'] = $wwWbConceptUri;

// Until we can scale redis memory we don't want to do this - https://github.com/addshore/wbstack/issues/37
$wgWBRepoSettings['sharedCacheType'] = CACHE_NONE;

# WikibaseLexeme, By default not enabled
if( $wikWiki->getSetting('wwExtEnableWikibaseLexeme') ) {
    wfLoadExtension( 'WikibaseLexeme' );
}

# WikibaseInWikitext (custom ext)
wfLoadExtension( 'WikibaseInWikitext' );
$wgWikibaseInWikitextSparqlDefaultUi = $wwWbSiteBaseUri . '/query';

# EntitySchema
wfLoadExtension( 'EntitySchema' );

# ULS
wfLoadExtension( 'UniversalLanguageSelector' );
wfLoadExtension( 'cldr' );

# Gadgets
# TODO load again once there is a fix for localization cache reload without DBhttps://phabricator.wikimedia.org/T237148
#wfLoadExtension( 'Gadgets' );

# TwoColConflict
wfLoadExtension( 'TwoColConflict' );
$wgTwoColConflictBetaFeature = false;

# OAuth
wfLoadExtension( 'OAuth' );
$wgGroupPermissions['sysop']['mwoauthproposeconsumer'] = true;
$wgGroupPermissions['sysop']['mwoauthmanageconsumer'] = true;
$wgGroupPermissions['sysop']['mwoauthviewprivate'] = true;
$wgGroupPermissions['sysop']['mwoauthupdateownconsumer'] = true;
$wgGroupPermissions['platform']['mwoauthproposeconsumer'] = true;
$wgGroupPermissions['platform']['mwoauthmanageconsumer'] = true;
$wgGroupPermissions['platform']['mwoauthviewprivate'] = true;
$wgGroupPermissions['platform']['mwoauthupdateownconsumer'] = true;

# JsonConfig
wfLoadExtension( 'JsonConfig' );

# Score
wfLoadExtension( 'Score' );

# Math
wfLoadExtension( 'Math' );

# Kartographer
wfLoadExtension( 'Kartographer' );

# Thanks
wfLoadExtension( 'Thanks' );

# Elastica & CirrusSearch
# TODO configure
#wfLoadExtension( 'Elastica' );
#require_once "$IP/extensions/CirrusSearch/CirrusSearch.php";

// TODO load the below extensions (need table updates)
// TODO allow the user to choose how signup happens on their wiki
// https://www.mediawiki.org/wiki/Extension:InviteSignup
//wfLoadExtension( 'InviteSignup' );
// https://www.mediawiki.org/wiki/Extension:ConfirmAccount
//require_once "$IP/extensions/ConfirmAccount/ConfirmAccount.php";

// If we have internal settings, and have been told to load them, then load them...
if( getenv('WBSTACK_LOAD_MW_INTERNAL') === 'yes' && file_exists( __DIR__ . '/InternalSettings.php' ) ) {
    // TODO add even more checks here?
    require_once __DIR__ . '/InternalSettings.php';
}

//// CUSTOM HOOKS
//TODO these should probably be in an extension...

// https://www.mediawiki.org/wiki/Manual:Hooks/PageContentSaveComplete
$wgHooks['PageContentSaveComplete'][] = function ( $wikiPage, $user, $mainContent, $summaryText, $isMinor, $isWatch, $section, &$flags, $revision, $status, $originalRevId, $undidRevId ) {
    global $wikWiki;
    $data = [
        'wiki_id' => $wikWiki->id,
        'title' => $wikiPage->getTitle()->getDBkey(),
        'namespace' => $wikiPage->getTitle()->getNamespace(),
    ];
    \DeferredUpdates::addCallableUpdate( function() use ( $data ) {
        $options = [];
        $options['userAgent'] = 'wikwiki PageContentSaveComplete wdqsupdater thingy';
        $options['method'] = 'POST';
        $options['timeout'] = 4;
        $options['postData'] = json_encode($data);
        $request = \MWHttpRequest::factory(
            'http://' . getenv( 'PLATFORM_API_BACKEND_HOST' ) . '/backend/event/pageUpdate',
            $options
        );
        $status = $request->execute();
        if ( !$status->isOK() ) {
            wfDebugLog('WBSTACK', 'Failed to call platform event/pageUpdate endpoint for PageContentSaveComplete: ' . $status->getStatusValue());
        }
    });
};

// https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDeleteComplete
$wgHooks['ArticleDeleteComplete'][] = function ( $wikiPage, &$user, $reason, $id, $content, $logEntry, $archivedRevisionCount ) {
    global $wikWiki;
    $data = [
        'wiki_id' => $wikWiki->id,
        'title' => $wikiPage->getTitle()->getDBkey(),
        'namespace' => $wikiPage->getTitle()->getNamespace(),
    ];
    \DeferredUpdates::addCallableUpdate( function() use ( $data ) {
        $options = [];
        $options['userAgent'] = 'wikwiki ArticleDeleteComplete wdqsupdater thingy';
        $options['method'] = 'POST';
        $options['timeout'] = 4;
        $options['postData'] = json_encode($data);
        $request = \MWHttpRequest::factory(
            'http://' . getenv( 'PLATFORM_API_BACKEND_HOST' ) . '/backend/event/pageUpdate',
            $options
        );
        $status = $request->execute();
        if ( !$status->isOK() ) {
            wfDebugLog('WBSTACK', 'Failed to call platform event/pageUpdate endpoint for ArticleDeleteComplete: ' . $status->getStatusValue());
        }
    });
};

// https://www.mediawiki.org/wiki/Manual:Hooks/TitleMoveComplete
$wgHooks['TitleMoveComplete'][] = function ( $title, $newTitle, $user, $oldid, $newid, $reason, $revision ) {
    global $wikWiki;
    $dataOne = [
        'wiki_id' => $wikWiki->id,
        'title' => $title->getDBkey(),
        'namespace' => $title->getNamespace(),
    ];
    \DeferredUpdates::addCallableUpdate( function() use ( $dataOne ) {
        $options = [];
        $options['userAgent'] = 'wikwiki TitleMoveComplete wdqsupdater thingy';
        $options['method'] = 'POST';
        $options['timeout'] = 4;
        $options['postData'] = json_encode($dataOne);
        $request = \MWHttpRequest::factory(
            'http://' . getenv( 'PLATFORM_API_BACKEND_HOST' ) . '/backend/event/pageUpdate',
            $options
        );
        $status = $request->execute();
        if ( !$status->isOK() ) {
            wfDebugLog('WBSTACK', 'Failed to call platform event/pageUpdate endpoint for TitleMoveComplete dataOne: ' . $status->getStatusValue());
        }
    });
    $dataTwo = [
        'wiki_id' => $wikWiki->id,
        'title' => $newTitle->getDBkey(),
        'namespace' => $newTitle->getNamespace(),
    ];
    \DeferredUpdates::addCallableUpdate( function() use ( $dataTwo ) {
        $options = [];
        $options['userAgent'] = 'wikwiki TitleMoveComplete wdqsupdater thingy';
        $options['method'] = 'POST';
        $options['timeout'] = 4;
        $options['postData'] = json_encode($dataTwo);
        $request = \MWHttpRequest::factory(
            'http://' . getenv( 'PLATFORM_API_BACKEND_HOST' ) . '/backend/event/pageUpdate',
            $options
        );
        $status = $request->execute();
        if ( !$status->isOK() ) {
            wfDebugLog('WBSTACK', 'Failed to call platform event/pageUpdate endpoint for TitleMoveComplete dataTwo: ' . $status->getStatusValue());
        }
    });
};

// https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDeleteComplete
$wgHooks['ArticleUndelete'][] = function ( $title, $create, $comment, $oldPageId, $restoredPages ) {
    global $wikWiki;
    $data = [
        'wiki_id' => $wikWiki->id,
        'title' => $title->getDBkey(),
        'namespace' => $title->getNamespace(),
    ];
    \DeferredUpdates::addCallableUpdate( function() use ( $data ) {
        $options = [];
        $options['userAgent'] = 'wikwiki ArticleUndelete wdqsupdater thingy';
        $options['method'] = 'POST';
        $options['timeout'] = 4;
        $options['postData'] = json_encode($data);
        $request = \MWHttpRequest::factory(
            'http://' . getenv( 'PLATFORM_API_BACKEND_HOST' ) . '/backend/event/pageUpdate',
            $options
        );
        $status = $request->execute();
        if ( !$status->isOK() ) {
            wfDebugLog('WBSTACK', 'Failed to call platform event/pageUpdate endpoint for ArticleUndelete: ' . $status->getStatusValue());
        }
    });
};

// https://www.mediawiki.org/wiki/Manual:Hooks/SkinBuildSidebar
$wgHooks['SkinBuildSidebar'][] = function ( $skin, &$sidebar ) use ( $wikWiki ) {
    $sidebar['Wikibase'][] = [
        'text'  => 'New Item',
        'href'  => '/wiki/Special:NewItem',
    ];
    $sidebar['Wikibase'][] = [
        'text'  => 'New Property',
        'href'  => '/wiki/Special:NewProperty',
    ];
    if( $wikWiki->getSetting('wwExtEnableWikibaseLexeme') ) {
        $sidebar['Wikibase'][] = [
            'text'  => 'New Lexeme',
            'href'  => '/wiki/Special:NewLexeme',
        ];
    }
    $sidebar['Wikibase'][] = [
        'text'  => 'New Schema',
        'href'  => '/wiki/Special:NewEntitySchema',
    ];
    $sidebar['Wikibase'][] = [
        'text'  => 'Query Service',
        'href'  => '/query/',
    ];
    $sidebar['Wikibase'][] = [
        'text'  => 'QuickStatements',
        'href'  => '/tools/quickstatements/',
    ];
};
