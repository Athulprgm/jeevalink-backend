#!/bin/sh
set -e

PORT="${PORT:-80}"

echo "=== Jeevalink API Starting ==="
echo "PORT=$PORT"
echo "APP_ENV=${APP_ENV}"

# Configure Apache to listen on Railway's dynamic PORT
sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/" /etc/apache2/sites-available/000-default.conf

echo "--- Running migrations ---"
php artisan migrate --force

echo "--- Starting Apache on port $PORT ---"
exec "$@"
