#!/bin/sh
set -e

echo "=== Jeevalink API Starting ==="
echo "PORT: ${PORT:-8080}"
echo "APP_ENV: ${APP_ENV}"

echo "--- Running migrations ---"
php artisan migrate --force

echo "--- Starting Laravel server on 0.0.0.0:${PORT:-8080} ---"
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
