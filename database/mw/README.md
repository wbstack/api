This directory contains the SQL needed to create and update wiki DBs.

**Todos**

 - Create steps to create update files....
 - Programmatically get the SQL instead of using adminer?
 - Allow mediawiki LS.php to be runable with no prefix (currently you end up with _tablename.....)
 - Allow mediawiki LS.php to be run with now redis etc for sql creation...
 - Allow mediawiki LS.php to be run with only a master?
 - Maybe actually allow doing this by running maint scripts instead? :/ (OR some internal only API?)
   - Could be all generated in "production" then? maybe?

**Versions**

- mw1.39-wbs1 - First 1.39 install
- mw1.38-wbs1 - First 1.38 install
- mw1.35-wbs1 - First 1.35 install
- mw1.34-wbs1 - First 1.34 install
- mw1.33-wbs5 - New extensions, TBA
- mw1.33-wbs4 - New extensions, Math is the only table (mathoid...)
- mw1.33-wbs3 - TRUNCATE l10n_cache table that we stopped using
- mw1.33-wbs2 - With EntitySchema table
- mw1.33-wbs1 - First version of 1.33

### Generating clean / fresh SQL

Make sure you have updated the docker-compose-clean.yml to:

- For adding extensions?
  - Include the latest version of the Mediawiki image with the new code added but not loaded (for extensions)
- For updating?
  - Updated the Mediawiki image tag
- Update maintWikWiki.json to match the defaults needs to load all extension from the mw image

**Start the setup:**

```
docker compose -f docker-compose-clean.yml up -d
```

**Check & wait for mysql access in adminer?**

http://localhost:1234/?server=sql-clean&username=root&db=wiki

You might get an error is MySql is not ready yet.

```SQLSTATE[HY000] [2002] Connection refused```

If so, retry.

**Then:**

```
docker compose -f docker-compose-clean.yml exec mediawiki bash
mv ./w/LocalSettings.php ./w/LocalSettings.php.temp
WBS_DOMAIN=maint php ./w/maintenance/install.php --dbserver sql-clean --dbuser root --dbpass toor --dbname wiki --with-extensions --pass AdminPass0 SiteName AdminName
```

**While** `WikibaseEdtf` causes `install.php` to fail with `--with-extensions`. As a workaround we specified all other extensions manually with `--extensions`, since it doesn't seem to add any tables itself and enabled it manually afterwards in LocalSettings.php (just like Wikibase)

This can be used to generate a comma-separated list of extensions:
```
cd ./w/extensions/ && ls -dm * | tr -d ' ' | tr -d \\n && cd ./../../
```

This is an example install command with `WikibaseEdtf` excluded:
```
WBS_DOMAIN=maint php ./w/maintenance/install.php --dbserver sql-clean --dbuser root --dbpass toor --dbname wiki --extensions AdvancedSearch,Auth_remoteuser,CirrusSearch,Cite,CodeEditor,CodeMirror,ConfirmAccount,ConfirmEdit,DeleteBatch,Echo,Elastica,EmbedVideo,EntitySchema,Gadgets,Graph,InviteSignup,JsonConfig,Kartographer,Mailgun,Math,MobileFrontend,MultimediaViewer,Nuke,OAuth,PageImages,ParserFunctions,Poem,Popups,RevisionSlider,Score,Scribunto,SecureLinkFixer,SpamBlacklist,StopForumSpam,SyntaxHighlight_GeSHi,TemplateData,TemplateSandbox,TextExtracts,Thanks,ThatSrc,TorBlock,TwoColConflict,UniversalLanguageSelector,WikiEditor,WikiHiero,Wikibase,WikibaseCirrusSearch,WikibaseExampleData,WikibaseInWikitext,WikibaseLexeme,WikibaseLexemeCirrusSearch,WikibaseManifest,cldr --pass AdminPass0 SiteName AdminName
```

**While** https://phabricator.wikimedia.org/T267809 is broken you'll then need to edit the auto generated LocalSettings.php file...
(per the instructions in the ticket)

Install an editor and open LocalSettings.php:
```
apt-get update && apt-get install -y vim
vi ./w/LocalSettings.php
```

Comment out:
```
//require_once "$IP/extensions/Wikibase/Wikibase.php";
```

Add the following:
```
wfLoadExtension( 'WikibaseRepository', $wgBaseDirectory . '/extensions/Wikibase/extension-repo.json' );
require_once "$wgBaseDirectory/extensions/Wikibase/repo/ExampleSettings.php";
wfLoadExtension( 'WikibaseClient', $wgBaseDirectory . '/extensions/Wikibase/extension-client.json' );
require_once "$wgBaseDirectory/extensions/Wikibase/client/ExampleSettings.php";
```

Run the update script:
```
WBS_DOMAIN=maint php ./w/maintenance/update.php --quick
WBS_DOMAIN=maint php ./w/maintenance/update.php --quick
WBS_DOMAIN=maint php ./w/maintenance/update.php --quick
WBS_DOMAIN=maint php ./w/maintenance/update.php --quick
```

**Then get the SQL from adminer:**

 - Navigate to http://localhost:1234
 - Log in with sql-clean, root, toor, wiki
 - Navigate to "wiki" DB
 - Click "Export" (near the top left)
 - Select these options:
   - Output: open
   - Format: SQL
   - Tables: CREATE
   - Data: INSERT
 - Deselect ALL DATA except "content_models", "interwiki", "slot_roles", "updatelog"
 - Review any tables that appear to be new to see if their data should be included...
 - Click "Export" (at the top)
 - Copy output to the "new" directory with correct name, and make alterations:
     - Remove SET statements
     - Update the sql file in cleanSql.php
     - Run cleanSql.php over the file: `php ./w/maintenance/wbaas-api/cleanSql.php`
     - Manually add the prefix string to any FOREIGN KEY statements. In the case of constraints be sure to prefix both the KEY and the table (See: [T333824](https://phabricator.wikimedia.org/T333824)). 
     - Where FOREIGN KEYs are used, you may need to change the order of exported tables! (To ensure tables are created before they are referenced)
 - Compare the resulting schemas to see what has changed... (use https://www.diffchecker.com/ ?)

**Cleanup the setup:**

```
docker compose -f docker-compose-clean.yml down --volumes
```

### Generating update / upgrade SQL

If the diff between SQLs is super easy, maybe you can just make the updates file yourself..

**READ THE README** for the update format, else you WILL get it wrong (\n\n etc...)

Make sure you have updated the docker-compose-upgrade.yml to:

 - Include the latest version of the Mediawiki image with the new code / extensions loaded
 - Include the **OLD** version of the schema for the update mysql service in the upgradeFrom.sql file
   - Make sure to setup the prefix to be SQL worthy.. ```/<<prefix>>_/prefix_/```

Troubleshooting:

 - Some extensions don't handle only outputting sql very well.. In these cases youll have to make your own update sql file...
   - Example, Echo in https://github.com/addshore/wbstack/issues/70

**Start the setup:**

```
docker compose -f docker-compose-upgrade.yml up -d
```

**Check & wait for mysql access in adminer?**

http://localhost:1234/?server=sql-upgrade&username=root&db=wiki

You might get an error is MySql is not ready yet.

```SQLSTATE[HY000] [2002] Connection refused```

If so, retry.

**Then:**

```
docker compose -f docker-compose-upgrade.yml exec mediawiki bash
WBS_DOMAIN=maint php ./w/maintenance/update.php --schema sql.sql --quick
cat sql.sql
```

**Cleanup the setup:**

```
docker compose -f docker-compose-upgrade.yml down --volumes
```
