<?php

// This script takes a raw set of SQL queries from MediaWiki with regular table names
// and adds a placeholder for a table prefix.
// At table creation time this prefix can then be replaced with an appropriate prefix.

// TODO allow passing in file? or run for all files?
$placeholder = '<<prefix>>_';
$filename = __DIR__ . '/new/mw1.34-wbs1.sql';

// Get the file
$text = file_get_contents($filename);

// Make sure there are no excessive whitepsace gaps
$text = preg_replace('~\r\n?~', "\n", $text);
$text = preg_replace("/\n+/s", "\n", $text);
// Remove any placeholders previously added to the file
$text = str_replace($placeholder, '', $text);

// Add new placeholders and space out the statements
$replacementPrefixes = [
  'CREATE TABLE `',
  'INSERT INTO `',
  'REPLACE INTO `',
];
foreach ($replacementPrefixes as $prefix) {
    $text = str_replace($prefix, "\n".$prefix.$placeholder, $text);
}

// Write the file
file_put_contents($filename, $text);

echo 'Done!'.PHP_EOL;
