#shellcheck disable=code
.PHONY: test-fresh init test lint

# Example: make test FILTER=tests/Jobs/ElasticSearchIndexInitTest.php
test:
	docker compose run --rm api vendor/bin/phpunit ${FILTER}

init:
	docker compose run --rm api bash -c 'php artisan migrate:fresh && php artisan passport:install && php artisan key:generate'

test-fresh: init test

lint:
	docker compose run --rm -T api vendor/bin/psalm
