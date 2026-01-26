test change

> ℹ️ Issues for this repository are tracked on [Phabricator](https://phabricator.wikimedia.org/project/board/5563/) - ([Click here to open a new one](https://phabricator.wikimedia.org/maniphest/task/edit/form/1/?tags=wikibase_cloud,wbstack_api
))

# WBStack Platform API
## Configuration
The platform API comes in a few different flavours that can be toggled using environment variables:

- `ROUTES_LOAD_WEB` - web routes for the public facing wbstack.com usecase
- `ROUTES_LOAD_SANDBOX` - web routes for the public facing sandbox usecase
- `ROUTES_LOAD_BACKEND` - internal only API endpoints (non public) for all usecases

This single application could likely be split up at some point. Everything is currently together to make use of the shared wiki management code and query service management code.

## Additional environment variables
### Contact page settings
- `WBSTACK_CONTACT_MAIL_SENDER` - The mail address of the sender address of the contact page mails. If the address includes the placeholder `<subject>`, it gets replaced with a shorthand for the actual selected subject (`give-feedback`, `report-a-problem`, etc)

- `WBSTACK_CONTACT_MAIL_RECIPIENT` - The receiver address of the contact page mails

## Developing

### Install dependencies

It is recommended to install the php dependencies via a composer docker container as that contains the required php extensions:
```
docker run --rm -it -v $PWD:/app -u $(id -u):$(id -g) composer install --ignore-platform-reqs
```

Alternatively, you can run `composer` on your local machine (ignoring platform requirements):
```
composer install --ignore-platform-reqs
```

### Initial setup

`cp .env.example .env` and modify the contents accordingly.

### Docker compose

You should be able to run some amount of this application in docker compose.

Though the experience is not that refined...

```sh
# Start the stack in the background
docker compose up -d

# Stop the stack and delete all volumes
docker compose down --volumes
```

### Migrations  

Run everything in one go:

```sh
docker compose exec api bash -c 'php artisan migrate:fresh && php artisan passport:install && php artisan db:seed && php artisan key:generate && php artisan storage:link'
```

Or each command separately:

```sh
# Create the SQL tables needed
docker compose exec api php artisan migrate:fresh

# Create some certs needed for authentication (passport is a laravel plugin)
docker compose exec api php artisan passport:install

# Seed some useful development data
docker compose exec api php artisan db:seed

# Generate and set the APP_KEY env variable.
docker compose exec api php artisan key:generate

# Create a symlink from `public/storage` to `storage/app/public`
docker compose exec api php artisan storage:link
```

Try loading http://localhost:8070/ until the DB is up and the connection works.

If you want to develop with the UI then simply point the UI docker compose setup to localhost:8082

### Seeded data

Some data is added to the database via the Laravel seeders.

You can log in with these details for example.

User: `a@a.a`
Password: `a`

And create a wiki.

### Testing

Currently most of the tests require the DB connection to exist.

```sh
# run the whole test suite
docker compose exec api vendor/bin/phpunit

# run a single file
docker compose exec api vendor/bin/phpunit tests/Jobs/DeleteWikiJobTest.php

# run a single class
docker compose exec api vendor/bin/phpunit --filter DeleteWikiJobTest

# run a single method
docker compose exec api vendor/bin/phpunit --filter DeleteWikiJobTest::testDeletesWiki
```

### Linting

```sh
docker compose exec api vendor/bin/pint --test -v
```

#### Debugging

If you get a CORS error from an API when testing, it might be due to an exception internally, resulting in a 500 response with no CORS.

If you are testing a route and believe an exception is happening (returning a 500), you can disable error handling to see the trace.

```php
use \Illuminate\Foundation\Testing\Concerns\InteractsWithExceptionHandling;

function someTest() {
    $this->withoutExceptionHandling();
    // rest of test code...
}
```

### Laravel IDE helper

You may need to run these from within a container with a DB attached:

```
php artisan ide-helper:models
php artisan ide-helper:eloquent
```
