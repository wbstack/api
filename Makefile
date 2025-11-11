#shellcheck disable=code
.PHONY: test-fresh init test lint

# Example: make test FILTER=tests/Jobs/ElasticSearchIndexInitTest.php
test:
	docker compose exec api bash -c 'LOG_CHANNEL=stderr LOG_LEVEL=debug vendor/bin/phpunit ${FILTER}'

init:
	docker compose exec api bash -c 'php artisan migrate:fresh && php artisan passport:install && php artisan key:generate'

test-fresh: init test

lint:
	docker compose exec api vendor/bin/pint --test -v

lint-fix:
	docker compose exec api vendor/bin/pint -v
