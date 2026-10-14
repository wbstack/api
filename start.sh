#!/usr/bin/env bash

set -e

role=${CONTAINER_ROLE:-app}
env=${APP_ENV:-production}

# if [ "$env" != "local" ]; then
#     echo "Caching configuration..."
#     (cd /var/www/html && php artisan config:cache && php artisan route:cache && php artisan view:cache)
# fi

if [ "$role" = "app" ]; then

    exec apache2-foreground

elif [ "$role" = "queue" ] && [ "$HORIZON_ENABLED" = "1" ]; then

    php /var/www/html/artisan horizon

elif [ "$role" = "queue" ]; then

    echo "Running the $queue_name queue..."
    # The `--timeout` and `--tries` options are queue worker defaults.
    # A job's `$tries` property or `tries()` method takes precedence over this `--tries` option.
    # https://laravel.com/framework/docs/11.x/queues#max-attempts
    # A job's `$timeout` property takes precedence over this `--timeout` option.
    # https://laravel.com/framework/docs/11.x/queues#timeout
    # A job's "timeout" value should always be less than the queue's `retry_after` value.
    # Otherwise, the job may be re-attempted before it has actually finished executing or timed out.
    # https://laravel.com/framework/docs/11.x/queues#job-expirations-and-timeouts
    php /var/www/html/artisan queue:work --verbose --tries=5 --timeout=90 --queue="$queue_name"

elif [ "$role" = "scheduler" ]; then

    while [ true ]
    do
      php /var/www/html/artisan schedule:run --verbose --no-interaction &
      sleep 60
    done

else
    echo "Could not match the container role \"$role\""
    exit 1
fi
