### Docs

Docs: https://lumen.laravel.com/docs/5.7
Routing: https://lumen.laravel.com/docs/5.7/routing
Testing: https://lumen.laravel.com/docs/5.7/testing

### Develop:

docker-compose up -d

Load http://localhost:8070/ until the DB is defiantly up and connection works

docker-compose exec api php artisan migrate:fresh
docker-compose exec api php artisan passport:install
docker-compose exec api php artisan db:seed

Run the tests:

docker-compose exec api vendor/bin/phpunit

### TODOS:
 - authorization for model changes (GATES?) https://lumen.laravel.com/docs/5.7/authorization
 - Make the models more delete,create,modify,etc?
 - See if ->getUser on request is auto filled by auth middleware?
   Suggested with ->user() function in docs at https://lumen.laravel.com/docs/5.7/authorization
 - MORE TESTS and figure out route test coverage?
 - re write routes file using route groups https://lumen.laravel.com/docs/5.7/routing#route-groups
 - use exists validation rather than own code https://lumen.laravel.com/docs/5.7/validation
 - Don't return 200 status code errors
 - Setup mail service provider https://lumen.laravel.com/docs/5.7/mail
