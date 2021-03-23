# WBStack Platform API

The platform API comes in a few different flavours that can be toggled using environment variables:

- ROUTES_LOAD_WEB - web routes for the public facing wbstack.com usecase
- ROUTES_LOAD_SANDBOX - web routes for the public facing sandbox usecase
- ROUTES_LOAD_BACKEND - internal only API endpoints (non public) for all usecases

This single application could likely be split up at some point.
Everything is currently together to make use of the shared wiki management code
and query service management code.

## Developing

### Install dependencies

`composer install`

### Initial setup

`cp .env.example .env` and modify the contents accordingly.

`docker-compose exec api php artisan key:generate` to generate and set the APP_KEY env variable.

### docker-compose

You should be able to run some amount of this application in docker-compose.

Though the experience is not that refined...

```sh
docker-compose up -d
```

Try loading http://localhost:8070/ until the DB is up and the connection works.

```sh
docker-compose exec api php artisan migrate:fresh
docker-compose exec api php artisan passport:install
docker-compose exec api php artisan db:seed
```

If you want to develop with the UI then simply point the UI docker-compose setup to localhost:8082

### Testing

Currently most of the tests require the DB connection to exist.

```sh
docker-compose exec api vendor/bin/phpunit
```

### Laravel IDE helper

You may need to run these from within a container with a DB attached:

```
php artisan ide-helper:models
php artisan ide-helper:eloquent
```
