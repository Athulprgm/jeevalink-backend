#!/bin/sh

set -e

echo "=== Jeevalink API Starting ==="
echo "PORT=$PORT"
echo "APP_ENV=$APP_ENV"

echo "--- Running migrations ---"
php artisan migrate --force || true

echo "--- Starting Apache ---"

exec "$@"

