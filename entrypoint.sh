#!/bin/sh

set -e

echo "=== Jeevalink API Starting ==="
echo "PORT=$PORT"
echo "APP_ENV=$APP_ENV"

echo "--- Configuring Apache PORT ---"
sed -i "s/Listen 80/Listen ${PORT:-80}/g" /etc/apache2/ports.conf || true
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${PORT:-80}>/g" /etc/apache2/sites-available/000-default.conf || true

echo "--- Fixing Apache MPM ---"
a2dismod mpm_event mpm_worker || true
a2enmod mpm_prefork || true

echo "--- Running migrations ---"
php artisan migrate --force || true

echo "--- Starting Apache ---"

exec "$@"

