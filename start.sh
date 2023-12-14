#!/usr/bin/env bash

set -e

role=${CONTAINER_ROLE:-app}
env=${APP_ENV:-production}
queue_name=${QUEUE_NAME:-default}

# if [ "$env" != "local" ]; then
#     echo "Caching configuration..."
#     (cd /var/www/html && php artisan config:cache && php artisan route:cache && php artisan view:cache)
# fi

if [ "$role" = "app" ]; then

    exec apache2-foreground

elif [ "$role" = "queue" ]; then

    echo "Running the $queue_name queue..."
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
