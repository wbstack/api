# WBStack Platform API

## Developing

### Install dependencies

`composer install`

### Initial setup

`cp .env.example .env` and modify the contents accordingly.

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

## Docker Image
### Environment Variables
The docker image built from this repository relies on the following environment variables:
#### Variables specific to this app
* `CONTAINER_ROLE`
* `ROUTES_LOAD_WEB`
* `ROUTES_LOAD_BACKEND`
* `ROUTES_LOAD_SANDBOX`


* `QUERY_SERVICE_HOST`
* `PLATFORM_MW_BACKEND_HOST`
* 
#### Used by laravel
* `APP_NAME`
* `APP_ENV`
* `APP_KEY`
* `APP_DEBUG`
* `APP_URL`
* `APP_TIMEZONE`


* `REDIS_HOST`
* `REDIS_PASSWORD`
* `REDIS_PORT`
* `REDIS_DB`
* `REDIS_CACHE_DB`
* `REDIS_PREFIX`


* `MAIL_DRIVER`
* `MAILGUN_DOMAIN`
* `MAILGUN_SECRET`
* `MAIL_FROM_ADDRESS`
* `MAIL_FROM_NAME`


* `INVISIBLE_RECAPTCHA_SITEKEY`
* `INVISIBLE_RECAPTCHA_SECRETKEY`
* `INVISIBLE_RECAPTCHA_BADGEHIDE`


* `GOOGLE_CLOUD_PROJECT_ID`
* `GOOGLE_CLOUD_STORAGE_BUCKET`
* `GOOGLE_CLOUD_STORAGE_KEY_FILE`


* `LOG_CHANNEL`
* `STACKDRIVER_ENABLED`
* `STACKDRIVER_PROJECT_ID`
* `STACKDRIVER_LOGGING_ENABLED`
* `STACKDRIVER_TRACING_ENABLED`
* `STACKDRIVER_ERROR_REPORTING_ENABLED`
* `STACKDRIVER_KEY_FILE_PATH`
* `STACKDRIVER_ERROR_REPORTING_BATCH_ENABLED`
* `STACKDRIVER_LOGGING_BATCH_ENABLED`


* `CACHE_DRIVER`
* `QUEUE_CONNECTION`
* `JWT_SECRET`


* `DB_CONNECTION`
* `DB_HOST_READ`
* `DB_HOST_WRITE`
* `DB_PORT`
* `DB_DATABASE`
* `DB_USERNAME`
* `DB_PASSWORD`
* `PASSPORT_PUBLIC_KEY`
* `PASSPORT_PRIVATE_KEY`