This directory contains the SQL needed to create and update wiki DDs.

### TODOS
 - Check if the update log table needs to have its row?
 - Create steps to create update files....
 - On start of wiki use we need to create a homepage and user...
 - Maybe actually allow do this by running maint scripts instead? :/

### Versions

*Future*
mw1.33-oc1 - First version of 1.33

### Generating SQL

Make sure you have updated the docker-compose.yml to:
 - Include the NEW version of the mediawiki image in both mediawiki services
 - Include the OLD version of the schema for the update mysql service in the upgradeFrom.sql file
   - Make sure to REMOVE the prefix things..

Start the setup:

  docker-compose up -d

### To generate a new schema for the given mediawiki images

<pre>
docker-compose exec mediawiki-clean bash
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
 - Compare the resulting schemas to see what has changed...

### To generate the updates needed for a schema?

Something like this? TODO do this....

<pre>
docker-compose exec mediawiki-upgrade bash
php ./maintenance/update.php --schema sql.sql --skip-compat-checks
</pre>
