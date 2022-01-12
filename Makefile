#shellcheck disable=code
.PHONY: test-and-migrate migrate test

# Example: make test FILTER=tests/Jobs/ElasticSearchIndexInitTest.php
test:
	docker-compose exec api vendor/bin/phpunit ${FILTER}

migrate:
	docker-compose exec api bash -c 'php artisan migrate:fresh && php artisan passport:install && php artisan db:seed && php artisan key:generate'

test-and-migrate: migrate test
