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
