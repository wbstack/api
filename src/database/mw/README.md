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

 - mw1.33-wbs5 - New extensions, TBA
 - mw1.33-wbs4 - New extensions, Math is the only table (mathoid...)
 - mw1.33-wbs3 - TRUNCATE l10n_cache table that we stopped using
 - mw1.33-wbs2 - With EntitySchema table
 - mw1.33-wbs1 - First version of 1.33

### Generating clean / fresh SQL

Make sure you have updated the docker-compose-clean.yml to:
 - Include the latest version of the Mediawiki image with the new code added but not loaded
 - Update maintWikWiki.json to match the defaults needs to load all extension from the mw image

**Start the setup:**

```docker-compose -f docker-compose-clean.yml up -d```

**Check & wait for mysql access in adminer?**

http://localhost:1234/?server=sql-clean&username=root&db=wiki

You might get an error is MySql is not ready yet.

```SQLSTATE[HY000] [2002] Connection refused```

If so, retry.

**Then:**

```
docker-compose -f docker-compose-clean.yml exec mediawiki bash
mv ./w/LocalSettings.php ./w/LocalSettings.php.temp
WW_DOMAIN=maint php ./w/maintenance/install.php --dbserver sql-clean --dbuser root --dbpass toor --dbname wiki --with-extensions --pass AdminPass SiteName AdminName
WW_DOMAIN=maint php ./w/maintenance/update.php --quick
WW_DOMAIN=maint php ./w/maintenance/update.php --quick
WW_DOMAIN=maint php ./w/maintenance/update.php --quick
WW_DOMAIN=maint php ./w/maintenance/update.php --quick
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
 - Deselect ALL DATA except "interwiki" and "updatelog"
 - Click "Export" (at the top)
 - Copy output to the "new" directory with correct name, and make alterations:
     - Remove SET statements
     - Update the sql file in cleanSql.php
     - Run cleanSql.php over the file
 - Compare the resulting schemas to see what has changed... (use https://www.diffchecker.com/ ?)

**Cleanup the setup:**

```docker-compose -f docker-compose-clean.yml down --volumes```

### Generating update / upgrade SQL

If the diff between SQLs is super easy, maybe you can just make the updates file yourself..

**READ THE README** for the update format, else you WILL get it wrong (\n\n etc...)

Make sure you have updated the docker-compose-upgrade.yml to:
 - Include the latest version of the Mediawiki image with the new code / extensions loaded
 - doMaintenance.php override is up to date (with the MW version loaded)
 - Include the **OLD** version of the schema for the update mysql service in the upgradeFrom.sql file
   - Make sure to setup the prefix to be SQL worthy.. ```/<<prefix>>_/prefix_/```
 - Copy LocalSettings.php from the new mediawiki image (which has new extensions loaded)
   - Comment out the REPLCIA db server, else the update will fail

Troubleshooting:
 - Some extensions don't handle only outputting sql very well.. In these cases youll have to make your own update sql file...
   - Example, Echo in https://github.com/addshore/wbstack/issues/70

**Start the setup:**

```docker-compose -f docker-compose-upgrade.yml up -d```

**Check & wait for mysql access in adminer?**

http://localhost:1234/?server=sql-upgrade&username=root&db=wiki&table=prefix_echo_email_batch

You might get an error is MySql is not ready yet.

```SQLSTATE[HY000] [2002] Connection refused```

If so, retry.

**Then:**

```
docker-compose -f docker-compose-upgrade.yml exec mediawiki bash
WW_DOMAIN=maint php ./maintenance/update.php --schema sql.sql --quick
cat sql.sql
```

**Cleanup the setup:**

```docker-compose -f docker-compose-upgrade.yml down --volumes```
