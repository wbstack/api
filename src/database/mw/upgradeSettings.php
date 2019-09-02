<?php

//# Database settings
$wgDBname = 'wiki';
$wgDBprefix = '';

$wgDBserver = 'sql-upgrade';
$wgDBuser = 'root';
$wgDBpassword = 'toor';
$wgDBtype = 'mysql';

$wgDBTableOptions = 'ENGINE=InnoDB, DEFAULT CHARSET=binary';

ini_set('xdebug.var_display_max_depth', -1);
ini_set('xdebug.var_display_max_children', -1);
ini_set('xdebug.var_display_max_data', -1);

error_reporting(-1);
ini_set('display_errors', 1);
$wgShowExceptionDetails = true;
$wgShowSQLErrors = true;
$wgDebugDumpSql = true;
$wgShowDBErrorBacktrace = true;

$wgShowDebug = false;
$wgDevelopmentWarnings = true;
//# Locale

$wgShellLocale = 'en_US.utf8';
$wgLanguageCode = 'en';

//# Keys

// TODO are these needed?
$wgAuthenticationTokenVersion = '1';
$wgUpgradeKey = 'r209109jr21';
$wgSecretKey = 'r09i21ri2iur091r09w1fk109fd9w1jd19jf1ji0f1';
