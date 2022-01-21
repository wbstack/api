#shellcheck disable=code
.PHONY: test-fresh init test lint

# Example: make test FILTER=tests/Jobs/ElasticSearchIndexInitTest.php
test:
	docker-compose exec api vendor/bin/phpunit ${FILTER}

init:
	docker-compose exec api bash -c 'php artisan migrate:fresh && php artisan passport:install && php artisan db:seed && php artisan key:generate'

test-fresh: init test

lint:
	docker-compose exec -T api vendor/bin/psalm

# Example: make test-xdebug FILTER=tests/Jobs/ElasticSearchIndexInitTest.php
test-xdebug:
	docker-compose exec api bash -c 'PHP_IDE_CONFIG="serverName=wbaas-local-api" XDEBUG_SESSION=1 php vendor/bin/phpunit ${FILTER}'

xdebug:
	docker-compose -f docker-compose.debug.yml up -d
