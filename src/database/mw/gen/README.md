docker-compose up -d

TODO work in progress.....

### To generate a new schema for the given mediawiki images

<pre>
docker-compose exec mediawiki-clean bash
php ./maintenance/install.php --dbserver sql-clean --dbuser root --dbpass toor --dbname wiki --with-extensions --pass AdminPass SiteName AdminName
php ./maintenance/update.php --quick
php ./maintenance/update.php --quick
</pre>

TODO:
 - Remove the user insertion
 - Create own default home page content?
 - Remove object cache?
 

### To generate the updates needed for a schema?

TBA....
