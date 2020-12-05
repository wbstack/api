# Summary of changes

## 1.34-wbs1 -> 1.35-wbs1

Archive table:

- ar_text_id
- ar_content_model
- ar_content_format

Interwiki table:

- From `(UNHEX('656D61637377696B69'),	'https://www.emacswiki.org/cgi-bin/wiki.pl?$1',	'',	UNHEX(''),	0,	0),`
- To `(UNHEX('656D61637377696B69'),	'https://www.emacswiki.org/emacs/$1',	'',	UNHEX(''),	0,	0)`
- TODO figure out if update.php will actually update this?

Other:

- Change unique index on ipblocks
- lang size changes on l10n_cache and langlinks
- 2x tables related to push notifications in Echo
- Oauth, adds tables and fields relating to oauth 2
- page page_restrictions defaults to NULL now
- revision, drop user command and content fields
- sites, increase global key and language
- updatelog changes
- watchlist_expiry table
- Wikibase new terms storage tables

## 1.33-wbs5 -> 1.34-wbs1

switch to actor only (and drop other cols) for:
 - archive
 - filearchive
 - image
 - ipblocks
 - logging
 - oldimage
 - recentchanges
 
insert some default data into:
 - content_models
 - slot_roles
 
update log has different data..