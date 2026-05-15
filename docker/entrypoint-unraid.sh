#!/usr/bin/env sh
set -eu

mkdir -p \
    storage/app/private \
    storage/app/public \
    storage/framework/cache \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

if [ "${1:-serve}" != "serve" ]; then
    exec "$@"
fi

php artisan storage:link 2>/dev/null || true

attempt=1
until php artisan migrate --force; do
    if [ "$attempt" -ge 10 ]; then
        echo "Database migrations failed after ${attempt} attempts."
        exit 1
    fi

    echo "Database is not ready yet; retrying migration in 5 seconds (${attempt}/10)."
    attempt=$((attempt + 1))
    sleep 5
done

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port=8000
