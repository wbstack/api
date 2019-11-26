This directory contains the SQL needed to create and update wiki DDs.

### TODOS
 - Create steps to create update files....
 - Programmatically get the SQL instead of using adminer?
 - On start of wiki use we need to create a homepage and user...
 - Allow mediawiki LS.php to be runable with no prefix (currently you end up with _tablename.....)
 - Allow mediawiki LS.php to be run with now redis etc for sql creation...
 - Allow mediawiki LS.php to be run with only a master?
 - Maybe actually allow doing this by running maint scripts instead? :/ (OR some internal only API?)
   - Could be all generated in "production" then? maybe?

### Versions

mw1.33-wbs2 - With EntitySchema table
mw1.33-wbs1 - First version of 1.33

### Generating clean / fresh SQL

Make sure you have updated the docker-compose-clean.yml to:
 - Include the latest version of the Mediawiki image
 - doMaintenance.php override is up to date

Start the setup:

  docker-compose -f docker-compose-clean.yaml up -d
  # WAIT and check you can connect via adminer?

Cleanup the setup:

  docker-compose -f docker-compose-clean.yaml down --volumes

Then:

<pre>
docker-compose -f docker-compose-clean.yaml exec mediawiki bash
mv LocalSettings.php LocalSettings.php.temp
php ./maintenance/install.php --dbserver sql-clean --dbuser root --dbpass toor --dbname wiki --with-extensions --pass AdminPass SiteName AdminName
php ./maintenance/update.php --quick
php ./maintenance/update.php --quick
php ./maintenance/update.php --quick
</pre>

Then get the SQL from adminer:

 - Navigate to http://localhost:1234
 - Log in with sql-clean, root, toor
 - Navigate to "wiki" DB
 - Click "Export" (near the top left)
 - Select options, Output: open, Format: SQL, Tables: CREATE, Data: INSERT
 - Select ONLY interwiki and updatelog data
 - Click "Export"
 - Copy output to the new directory with correct name, and make alterations:
     - Remove SET statements
     - Run cleanSql.php over the file
 - Compare the resulting schemas to see what has changed... (use https://www.diffchecker.com/ ?)

### Generating update / upgrade SQL

Make sure you have updated the docker-compose-upgrade.yml to:
 - Include the latest version of the Mediawiki image
 - doMaintenance.php override is up to date
 - Include the OLD version of the schema for the update mysql service in the upgradeFrom.sql file
   - Make sure to setup the prefix to be SQL worthy.. /<<prefix>>_/prefix_/

Start the setup:

  docker-compose -f docker-compose-upgrade.yml up -d

Cleanup the setup:

  docker-compose -f docker-compose-upgrade.yaml down --volumes

Then:

<pre>
docker-compose -f docker-compose-upgrade.yml exec mediawiki bash
WW_DOMAIN=maint php ./maintenance/update.php --schema sql.sql --quick
cat sql.sql
</pre>

